=== JeyTech Checkout Hours for WooCommerce ===
Contributors: jeytech
Tags: woocommerce, checkout, opening hours, weekly schedule, store hours
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Définissez les horaires hebdomadaires de commande tout en laissant le catalogue et le panier accessibles. Compatible avec les caisses classique et à blocs.

== Description ==

JeyTech Checkout Hours for WooCommerce vous permet de choisir quand les clients peuvent passer de nouvelles commandes. Les produits restent visibles et les clients peuvent continuer à remplir leur panier en dehors de vos horaires de commande.

Configurez votre planning dans **WooCommerce → Checkout Hours**. L’extension est désactivée à l’installation et reste inactive tant que vous n’avez pas enregistré et activé un planning d’ouverture valide.

* Plusieurs créneaux d’ouverture par jour de la semaine, avec des pauses possibles.
* Créneaux traversant minuit et journées complètes au format 00:00–24:00.
* Fuseau horaire de WordPress, avec prise en compte des changements d’heure.
* Blocage des commandes ou acceptation avec un avertissement en dehors des horaires.
* Protection côté serveur pour les caisses classique et à blocs de WooCommerce.
* Bandeau facultatif sur le site et code court `[jeytech_checkout_hours]`.
* Actualisation de l’état sur les pages mises en cache ; la validation finale est toujours contrôlée sur le serveur.
* Compatible avec le stockage haute performance des commandes (HPOS).

Les heures d’ouverture sont incluses et les heures de fermeture sont exclues. Une heure de fermeture antérieure à l’ouverture prolonge le créneau jusqu’au lendemain. Les créneaux qui se chevauchent ne peuvent pas être enregistrés.

Le mode avertissement accepte les commandes normalement. Il ne réserve pas de créneaux de retrait, ne retarde pas le paiement et ne programme pas le traitement. Le paiement des commandes existantes, la consultation du catalogue et les modifications du panier restent disponibles.

L’extension ne fait appel à aucun service externe et n’intègre aucun suivi. Le point de terminaison d’état est accessible en lecture seule sur votre propre site WordPress. Sa réponse doit être exclue de tout cache de serveur mandataire ou de CDN qui ignore les en-têtes no-store.

Prérequis : WordPress 6.6 ou ultérieur, WooCommerce 9.6 ou ultérieur et PHP 7.4 ou ultérieur.

== Installation ==

1. Installez et activez WooCommerce.
2. Téléversez le dossier de l’extension dans `/wp-content/plugins/` ou installez-la depuis WordPress.
3. Activez JeyTech Checkout Hours for WooCommerce.
4. Ouvrez WooCommerce → Checkout Hours et vérifiez le fuseau horaire de la boutique.
5. Ajoutez les créneaux d’ouverture, choisissez le mode hors horaires, activez l’extension et enregistrez.

== Frequently Asked Questions ==

= Les clients peuvent-ils ajouter des produits au panier en dehors des horaires de commande ? =

Oui. En mode blocage, seule la création de nouvelles commandes est limitée. Le catalogue et le panier restent accessibles.

= Les deux caisses de WooCommerce sont-elles prises en charge ? =

Oui. L’extension contrôle la caisse classique sur le serveur et refuse une nouvelle commande de la caisse à blocs avant que la Store API ne la crée ou ne la traite.

= Quel fuseau horaire est utilisé ? =

Le fuseau configuré dans Réglages → Général de WordPress. Choisissez un fuseau associé à une ville, comme Europe/Paris, pour bénéficier des changements d’heure automatiques.

= Comment saisir un créneau traversant minuit ou une journée complète ? =

Utilisez 22:00–02:00 pour un créneau traversant minuit. Il se termine à 02:00 le lendemain. Utilisez 00:00–24:00 pour une journée complète. Laissez un jour vide pour le fermer.

= Que se passe-t-il lors d’un changement d’heure ? =

Les créneaux suivent l’heure locale. Une heure répétée suit le même planning lors de ses deux occurrences. Une heure supprimée ne correspond à aucun instant réel ; la prochaine ouverture est le premier instant réel situé dans un créneau d’ouverture.

= L’extension fonctionne-t-elle sur une page mise en cache ou sans JavaScript ? =

La validation finale est protégée sur le serveur, indépendamment du cache et de JavaScript. Les notices sont actualisées avec JavaScript depuis un point de terminaison local sans cache. Le thème doit prendre en charge wp_body_open pour afficher le bandeau facultatif sur le site.

= Le paiement d’une commande existante est-il bloqué ? =

Non. Les pages de paiement des commandes existantes et les routes de la Store API propres à une commande restent accessibles.

= L’extension est-elle traduite ? =

Les chaînes sources sont en anglais. Les traductions sont distribuées par les paquets de langue WordPress.org après validation par la communauté. Aucun catalogue ni chargement de langue personnalisé n’est intégré à l’archive.

== Screenshots ==

1. Planning hebdomadaire avec plusieurs créneaux, horaires traversant minuit et aperçu de l’heure du fuseau de la boutique.
2. Caisse classique en dehors des horaires de commande, avec conservation du panier.
3. Caisse à blocs en dehors des horaires de commande, avec protection côté serveur.

== Changelog ==

= 1.0.0 =
* Première version : horaires hebdomadaires de commande, protection des caisses classique et à blocs, notices actualisées et moteur de fuseau horaire commun.
