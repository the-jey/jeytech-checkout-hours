<?php
/** Pure weekly schedule and timezone engine. @package JeyTech\CheckoutHours */
namespace JeyTech\CheckoutHours;

defined( 'ABSPATH' ) || exit;

/** Evaluates local wall-clock ranges, including DST gaps and repeated hours. */
final class Schedule {
 const WEEK = 10080;

 /** Parse a 24-hour time; 24:00 is allowed only as an end.
  * @param mixed $value Time.
  * @param bool  $end Whether this is a closing time.
  * @return int|null Minutes since midnight, or null.
  */
 public static function minutes( $value, bool $end = false ): ?int {
  if ( ! is_string( $value ) ) { return null; }
  if ( $end && '24:00' === $value ) { return 1440; }
  if ( ! preg_match( '/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $value ) ) { return null; }
  return (int) substr( $value, 0, 2 ) * 60 + (int) substr( $value, 3, 2 );
 }

 /** Validate and normalize a whole weekly schedule.
  * @param mixed $days ISO weekdays 1–7 containing start/end ranges.
  * @return array|\WP_Error Normalized schedule or error.
  */
 public static function normalize( $days ) {
  if ( ! is_array( $days ) ) { return new \WP_Error( 'schedule', __( 'The weekly schedule is invalid.', 'jeytech-checkout-hours' ) ); }
  $normalized = array();
  for ( $day = 1; $day <= 7; ++$day ) {
   $normalized[ $day ] = array();
   $rows = $days[ $day ] ?? array();
   if ( ! is_array( $rows ) || count( $rows ) > 12 ) { return new \WP_Error( 'schedule', __( 'Use at most 12 time ranges per day.', 'jeytech-checkout-hours' ) ); }
   foreach ( $rows as $row ) {
    if ( ! is_array( $row ) ) { return new \WP_Error( 'schedule', __( 'The weekly schedule is invalid.', 'jeytech-checkout-hours' ) ); }
    $start = $row['start'] ?? '';
    $end = $row['end'] ?? '';
    if ( '' === $start && '' === $end ) { continue; }
    $a = self::minutes( $start );
    $b = self::minutes( $end, true );
    if ( null === $a || null === $b || $a === $b ) {
     return new \WP_Error( 'schedule', __( 'Enter valid HH:MM times. Opening and closing must differ; use 00:00–24:00 for a full day.', 'jeytech-checkout-hours' ) );
    }
    $normalized[ $day ][] = array( 'start' => $start, 'end' => $end );
   }
   usort( $normalized[ $day ], static function ( $a, $b ) { return strcmp( $a['start'], $b['start'] ); } );
  }
  $previous_end = -1;
  foreach ( self::intervals( $normalized ) as $range ) {
   if ( $range[0] < $previous_end ) { return new \WP_Error( 'schedule', __( 'Time ranges overlap, including an overnight range on the following day.', 'jeytech-checkout-hours' ) ); }
   $previous_end = $range[1];
  }
  return $normalized;
 }

 /** Flatten validated ranges and split Sunday overnights at the week boundary.
  * @param array $days Validated schedule.
  * @return array Sorted intervals in minutes of the week.
  */
 public static function intervals( array $days ): array {
  $intervals = array();
  foreach ( $days as $day => $rows ) {
   if ( (int) $day < 1 || (int) $day > 7 || ! is_array( $rows ) ) { continue; }
   foreach ( $rows as $row ) {
    $a = self::minutes( $row['start'] ?? '' );
    $b = self::minutes( $row['end'] ?? '', true );
    if ( null === $a || null === $b || $a === $b ) { continue; }
    $start = ( (int) $day - 1 ) * 1440 + $a;
    $end = ( (int) $day - 1 ) * 1440 + $b + ( $b < $a ? 1440 : 0 );
    $intervals[] = array( $start, min( $end, self::WEEK ) );
    if ( $end > self::WEEK ) { $intervals[] = array( 0, $end - self::WEEK ); }
   }
  }
  usort( $intervals, static function ( $a, $b ) { return $a[0] <=> $b[0]; } );
  return $intervals;
 }

 /** Check an absolute UTC instant against local wall-clock intervals.
  * @param array         $ranges Flattened intervals.
  * @param int           $timestamp Unix timestamp.
  * @param \DateTimeZone $zone Store timezone.
  */
 private static function contains( array $ranges, int $timestamp, \DateTimeZone $zone ): bool {
  $utc = new \DateTimeImmutable( '@' . $timestamp );
  $wall = $timestamp + $zone->getOffset( $utc );
  $minute = ( (int) gmdate( 'N', $wall ) - 1 ) * 1440 + (int) gmdate( 'G', $wall ) * 60 + (int) gmdate( 'i', $wall );
  foreach ( $ranges as $range ) {
   if ( $minute >= $range[0] && $minute < $range[1] ) { return true; }
  }
  return false;
 }

 /** Evaluate an instant and locate the next real opening, without assuming 24-hour days.
  * @param array              $days Validated schedule.
  * @param int           $timestamp Absolute Unix timestamp.
  * @param \DateTimeZone $zone Store timezone.
  * @return array Open state and next opening as an absolute Unix timestamp or null.
  */
 public static function evaluate( array $days, int $timestamp, \DateTimeZone $zone ): array {
  $ranges = self::intervals( $days );
  if ( self::contains( $ranges, $timestamp, $zone ) ) { return array( 'open' => true, 'next' => null ); }
  if ( ! $ranges ) { return array( 'open' => false, 'next' => null ); }
  $utc = new \DateTimeImmutable( '@' . $timestamp );
  $until = $timestamp + 9 * DAY_IN_SECONDS;
  $transitions = $zone->getTransitions( $timestamp - 2 * DAY_IN_SECONDS, $until );
  $offsets = array( $zone->getOffset( $utc ) );
  $candidates = array();
  if ( is_array( $transitions ) ) {
   foreach ( $transitions as $transition ) {
    $offsets[] = (int) $transition['offset'];
    if ( $transition['ts'] > $timestamp ) { $candidates[] = (int) $transition['ts']; }
   }
  }
  // Map each local start to all valid UTC instants. A repeated hour has two;
  // a skipped hour has none. Timezone transitions also catch openings inside a gap.
  $date = new \DateTimeImmutable( gmdate( 'Y-m-d', $timestamp + $zone->getOffset( $utc ) ) . ' 12:00:00', $zone );
  for ( $i = 0; $i <= 8; ++$i ) {
   $day = $date->modify( '+' . $i . ' days' );
   foreach ( $days[ (int) $day->format( 'N' ) ] ?? array() as $row ) {
    $wall = $day->format( 'Y-m-d' ) . ' ' . $row['start'] . ':00';
    $naive = new \DateTimeImmutable( $wall, new \DateTimeZone( 'UTC' ) );
    foreach ( array_unique( $offsets ) as $offset ) {
     $candidate = $naive->getTimestamp() - $offset;
     $candidate_utc = new \DateTimeImmutable( '@' . $candidate );
     $local_wall = gmdate( 'Y-m-d H:i:s', $candidate + $zone->getOffset( $candidate_utc ) );
     if ( $candidate > $timestamp && $local_wall === $wall ) { $candidates[] = $candidate; }
    }
   }
  }
  sort( $candidates, SORT_NUMERIC );
  foreach ( array_unique( $candidates ) as $candidate ) {
   if ( self::contains( $ranges, $candidate, $zone ) ) { return array( 'open' => false, 'next' => $candidate ); }
  }
  return array( 'open' => false, 'next' => null );
 }
}
