-- L'Abside : base de démarrage (structure + contenu, sans sessions, cache, jetons ni inscriptions).
-- À importer dans une base VIDE, avant le premier ./deploy.sh (les migrations déjà appliquées sont enregistrées).
SET FOREIGN_KEY_CHECKS=0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `event_dates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_dates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint unsigned NOT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_dates_event_id_foreign` (`event_id`),
  KEY `event_dates_starts_at_index` (`starts_at`),
  CONSTRAINT `event_dates_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `event_dates` WRITE;
/*!40000 ALTER TABLE `event_dates` DISABLE KEYS */;
INSERT INTO `event_dates` VALUES (1,2,'2026-12-12 20:30:00',NULL,NULL,'2026-11-05 10:41:25','2026-11-05 10:41:25'),(2,3,'2027-02-06 20:30:00',NULL,NULL,'2026-11-05 10:41:26','2026-11-05 10:41:26'),(3,4,'2027-04-03 20:30:00',NULL,NULL,'2026-11-05 10:41:27','2026-11-05 10:41:27'),(4,5,'2026-10-09 14:30:00',NULL,NULL,'2026-11-05 10:41:27','2026-11-05 10:41:27'),(5,5,'2026-11-06 14:30:00',NULL,'La Transfiguration par Raphaël','2026-11-05 10:41:27','2026-11-05 10:41:27'),(6,5,'2026-12-04 14:30:00',NULL,NULL,'2026-11-05 10:41:27','2026-11-05 10:41:27'),(7,5,'2027-01-08 14:30:00',NULL,NULL,'2026-11-05 10:41:27','2026-11-05 10:41:27'),(8,5,'2027-02-12 14:30:00',NULL,NULL,'2026-11-05 10:41:27','2026-11-05 10:41:27'),(9,5,'2027-03-19 14:30:00',NULL,NULL,'2026-11-05 10:41:27','2026-11-05 10:41:27'),(10,5,'2027-04-16 14:30:00',NULL,NULL,'2026-11-05 10:41:27','2026-11-05 10:41:27'),(11,5,'2027-05-14 14:30:00',NULL,NULL,'2026-11-05 10:41:27','2026-11-05 10:41:27'),(12,6,'2026-11-24 19:30:00',NULL,NULL,'2026-11-05 10:41:28','2026-11-05 10:41:28'),(13,7,'2026-12-01 19:30:00',NULL,NULL,'2026-11-05 10:41:28','2026-11-05 10:41:28'),(14,8,'2026-11-05 14:00:00',NULL,NULL,'2026-11-05 10:41:29','2026-11-05 10:41:29');
/*!40000 ALTER TABLE `event_dates` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `event_keyword`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_keyword` (
  `event_id` bigint unsigned NOT NULL,
  `keyword_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`event_id`,`keyword_id`),
  KEY `event_keyword_keyword_id_foreign` (`keyword_id`),
  CONSTRAINT `event_keyword_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_keyword_keyword_id_foreign` FOREIGN KEY (`keyword_id`) REFERENCES `keywords` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `event_keyword` WRITE;
/*!40000 ALTER TABLE `event_keyword` DISABLE KEYS */;
INSERT INTO `event_keyword` VALUES (5,1),(6,1),(7,1),(8,1),(2,2),(3,2),(4,2),(4,3);
/*!40000 ALTER TABLE `event_keyword` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `place_id` bigint unsigned DEFAULT NULL,
  `intro` text COLLATE utf8mb4_unicode_ci,
  `body` longtext COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_alt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `poster` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gallery` json DEFAULT NULL,
  `specs` json DEFAULT NULL,
  `partners` json DEFAULT NULL,
  `is_free` tinyint(1) NOT NULL DEFAULT '1',
  `price_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'optional',
  `capacity` int unsigned DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_spotlight` tinyint(1) NOT NULL DEFAULT '0',
  `schedule_mode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'dates',
  `recurrence` json DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `preview_token` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `events_slug_unique` (`slug`),
  UNIQUE KEY `events_preview_token_unique` (`preview_token`),
  KEY `events_place_id_foreign` (`place_id`),
  KEY `events_is_published_type_index` (`is_published`,`type`),
  CONSTRAINT `events_place_id_foreign` FOREIGN KEY (`place_id`) REFERENCES `places` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
INSERT INTO `events` VALUES (2,'Au nom de la mère','au-nom-de-la-mere','theatre',1,'Erri De Luca s\'empare de l\'histoire la plus connue de l\'humanité, la Nativité, et l\'articule autour de la figure de Marie.','Ou plutôt de Miriàm, une simple jeune femme juive, fiancée à Iosef quand elle tombe enceinte. « *Au nom de la mère* met en scène une Marie inouïe de candeur et de force », fait observer Francesco Agnello. « Cette force lui permet de convaincre Joseph de les aimer, elle et son enfant, malgré une faute qui devrait l\'envoyer à la lapidation et à la mort ».\n\nSous la plume du romancier italien, l\'histoire de la Nativité trouve un ancrage nouveau dans le contexte hébraïque, et se fait éloge d\'un corps et d\'une âme, ceux d\'une mère.','evenements/au-nom-de-la-mere-iv7FuK.webp','Les comédiens d\'Au nom de la mère dans une église','evenements/affiches/affiche-au-nom-de-la-mere-SbuqlV.webp',NULL,'[{\"key\": \"D\'après\", \"value\": \"Erri De Luca\"}, {\"key\": \"Mise en scène et musique\", \"value\": \"Francesco Agnello\"}, {\"key\": \"Avec\", \"value\": \"Gérard Rouzier\\nJohanna Berrebi\"}]','[{\"alt\": \"Paroisse Saint-Jérôme, diocèse de Nice, La Procure, Accrorgue, RCF Notre-Dame\", \"path\": \"evenements/partenaires/partenaires-theatre-i2ByQn.webp\"}]',1,NULL,'optional',NULL,1,0,'dates',NULL,1,'LK2zXXrcEgVQ23aVxMDAdiniveaL76wzDGtyM41x','2026-11-05 10:41:25','2026-11-05 10:41:25'),(3,'Pierre & Mohamed','pierre-et-mohamed','theatre',1,'Le Théâtre de l\'Abside présente Pierre & Mohamed, d\'Adrien Candiard.',NULL,'evenements/pierre-et-mohamed-tXH0cl.webp','Le comédien de Pierre & Mohamed sur scène','evenements/affiches/affiche-pierre-et-mohamed-k0uPho.webp',NULL,'[{\"key\": \"Texte\", \"value\": \"Adrien Candiard\"}, {\"key\": \"Mise en scène et musique\", \"value\": \"Francesco Agnello\"}, {\"key\": \"Avec\", \"value\": \"Raphaël Joly\"}]','[{\"alt\": \"Paroisse Saint-Jérôme, diocèse de Nice, La Procure, Accrorgue, RCF Notre-Dame\", \"path\": \"evenements/partenaires/partenaires-theatre-i2ByQn.webp\"}]',1,NULL,'optional',NULL,1,0,'dates',NULL,1,'LWZRD4xHQl1BsFVeQbEGy6Bh1Jt1NsR6UGOlQNnq','2026-11-05 10:41:26','2026-11-05 10:41:26'),(4,'Reste avec nous','reste-avec-nous','theatre',1,'Les pèlerins d\'Emmaüs : une lecture spectacle d\'après le texte d\'Henri Guillemin.',NULL,'evenements/reste-avec-nous-xS6GzR.webp','Les pieds d\'un pèlerin sur un chemin de pierre','evenements/affiches/affiche-reste-avec-nous-H4qfAG.webp',NULL,'[{\"key\": \"Texte\", \"value\": \"Henri Guillemin\"}, {\"key\": \"Adaptation théâtrale\", \"value\": \"Benoît Allemane\"}, {\"key\": \"Lecture spectacle avec\", \"value\": \"Serge Barbuscia\\nPatrick Courtois\"}]','[{\"alt\": \"Paroisse Saint-Jérôme, diocèse de Nice, La Procure, Accrorgue, RCF Notre-Dame\", \"path\": \"evenements/partenaires/partenaires-theatre-i2ByQn.webp\"}]',1,NULL,'optional',NULL,0,1,'dates',NULL,1,'wrprS57q0nlx9Ffep7tJDLZUSfyff71NiMUoAjwo','2026-11-05 10:41:27','2026-11-05 10:41:27'),(5,'Conférences Art & Foi','conferences-art-et-foi','conference',2,'D\'octobre 2026 à mai 2027, une conférence par mois pour découvrir la profondeur spirituelle des chefs-d\'œuvre de la peinture religieuse.','Nos conférences visent à transmettre la beauté de l\'art par une compréhension intégrale de l\'œuvre : histoire, art et spiritualité sont ainsi abordés en miroir. Les conférences sont gratuites et ouvertes à tous.','evenements/transfiguration-q8k9PA.webp','La Transfiguration de Raphaël (détail)','evenements/affiches/affiche-cycle-art-et-foi-YIsCgm.webp',NULL,'[{\"key\": \"Intervenante\", \"value\": \"Christel Naujoks, PhD, déléguée à la Culture du diocèse de Nice\"}]',NULL,1,NULL,'optional',NULL,0,0,'dates',NULL,1,'d3dMH8pWvC74q4izpMbbojtp1x8ZMDSk8PLtcH1W','2026-11-05 10:41:27','2026-11-05 10:41:27'),(6,'Architecture et liturgie','architecture-et-liturgie','conference',3,'Le sens des églises à la lumière de l\'histoire et de la liturgie.',NULL,'evenements/architecture-liturgie-kNd4cV.webp','Le chœur d\'une église','evenements/affiches/affiche-architecture-liturgie-oKcLKz.webp',NULL,'[{\"key\": \"Intervenante\", \"value\": \"Christel Naujoks, PhD, déléguée à la Culture du diocèse de Nice\"}]',NULL,1,NULL,'optional',NULL,0,0,'dates',NULL,1,'9Fv4bsq2xA6FSTxXIA7DzbqKaG7yoHuYrTiPzNVA','2026-11-05 10:41:28','2026-11-05 10:41:28'),(7,'La Nativité : ce que la peinture nous dit de Dieu','la-nativite-ce-que-la-peinture-nous-dit-de-dieu','conference',3,'Parlons d\'Art & Foi : la Nativité dans la peinture.',NULL,'evenements/nativite-PWmyVQ.webp','Une Nativité peinte (détail)','evenements/affiches/affiche-nativite-GS7iJ9.webp',NULL,'[{\"key\": \"Intervenante\", \"value\": \"Christel Naujoks, PhD, déléguée à la Culture du diocèse de Nice\"}]',NULL,1,NULL,'optional',NULL,0,0,'dates',NULL,1,'tw3YFSaWTZrMdvcP5kjA9WGlDMyU1obEecWcl2vm','2026-11-05 10:41:28','2026-11-05 10:41:28'),(8,'Visite de la chapelle Matisse','visite-de-la-chapelle-matisse','visite',4,'Une sortie Art & Foi pour découvrir la chapelle décorée par Henri Matisse à Vence.','Rendez-vous devant la chapelle.\n\n- **Parking** sur l\'avenue Matisse ou à Vence\n- **Bus 9** : de Nice Grands Arénas à Vence','evenements/chapelle-matisse-K8Oe2J.webp','Les vitraux de la chapelle Matisse','evenements/affiches/affiche-chapelle-matisse-PgCpz2.webp',NULL,NULL,NULL,1,'Entrée chapelle et musée : 7 € par personne','required',NULL,1,0,'dates',NULL,1,'dAizDPWt9P7WMMAZQIJuaNtz5OxlCPhaca1c3zin','2026-11-05 10:41:29','2026-11-05 10:41:29');
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `keywords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `keywords` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `has_page` tinyint(1) NOT NULL DEFAULT '0',
  `intro` text COLLATE utf8mb4_unicode_ci,
  `body` longtext COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_alt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gallery` json DEFAULT NULL,
  `when_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `where_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `keywords_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `keywords` WRITE;
/*!40000 ALTER TABLE `keywords` DISABLE KEYS */;
INSERT INTO `keywords` VALUES (1,'Art & Foi','art-foi',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 10:41:10','2026-11-05 10:41:10'),(2,'Théâtre de l\'Abside','theatre-de-labside',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 10:41:10','2026-11-05 10:41:10'),(3,'Lecture spectacle','lecture-spectacle',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 10:41:10','2026-11-05 10:41:10'),(4,'Nuit des églises','nuit-des-eglises',1,'Une manifestation cultuelle et culturelle inscrite dans le paysage estival des diocèses depuis 2011.','La Nuit des églises, initiée par l\'Église de France, bénéficie d\'une reconnaissance à l\'échelle nationale : plusieurs centaines d\'églises y participent chaque année.','mots-cles/nuit-img-7899-wi8skz.webp','Vitraux projetés sur la façade d\'une église, la nuit, devant le public','[{\"alt\": \"Bougies sur les marches d\'un parvis\", \"path\": \"mots-cles/galerie/nuit-img-7427-neL68n.webp\"}, {\"alt\": \"Bougies le long des marches d\'une église\", \"path\": \"mots-cles/galerie/nuit-img-7447-hga9eX.webp\"}, {\"alt\": \"Le public devant une église illuminée, la nuit\", \"path\": \"mots-cles/galerie/nuit-img-7492-4LSdHD.webp\"}]',NULL,'Églises des Alpes-Maritimes','2026-11-05 10:41:10','2026-11-05 10:41:31');
/*!40000 ALTER TABLE `keywords` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_10_29_100100_create_settings_table',1),(5,'2026_10_29_100200_seed_initial_content',1),(6,'2026_10_29_203519_create_oauth_auth_codes_table',1),(7,'2026_10_29_203520_create_oauth_access_tokens_table',1),(8,'2026_10_29_203521_create_oauth_refresh_tokens_table',1),(9,'2026_10_29_203522_create_oauth_clients_table',1),(10,'2026_10_29_203523_create_oauth_device_codes_table',1),(11,'2026_10_30_130000_add_activity_dates_to_users_table',1),(12,'2026_11_05_100000_create_events_tables',2),(13,'2026_11_05_100100_seed_events',3),(14,'2026_11_05_200000_create_registrations_tables',4);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `places`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `places` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `places` WRITE;
/*!40000 ALTER TABLE `places` DISABLE KEYS */;
INSERT INTO `places` VALUES (1,'Église Saint-Étienne','32 rue Vernier','06000','Nice',43.7070750,7.2589230,'2026-11-05 10:41:10','2026-11-05 10:41:10'),(2,'Maison diocésaine','29 boulevard Franck Pilatte','06300','Nice',43.6915540,7.2921890,'2026-11-05 10:41:10','2026-11-05 10:41:10'),(3,'Centre Saint-Joseph','1104 route de Nice','06650','Le Rouret',43.6770040,7.0174810,'2026-11-05 10:41:10','2026-11-05 10:41:10'),(4,'Chapelle Matisse','466 avenue Henri Matisse','06140','Vence',43.7275200,7.1128740,'2026-11-05 10:41:10','2026-11-05 10:41:10');
/*!40000 ALTER TABLE `places` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('home','{\"hero\": {\"text\": \"L\'Abside fait découvrir le patrimoine religieux et culturel du diocèse de Nice, à travers des expositions, visites, conférences et spectacles ouverts à tous.\", \"image\": \"accueil/accueil-facade-DEjRVk.webp\", \"title\": \"Patrimoine, culture et création contemporaine\", \"image_alt\": \"Façade jaune d\'une église sur un ciel bleu\"}, \"about\": {\"text\": \"Lieu de rencontre entre patrimoine, culture et création contemporaine, L\'Abside encourage l\'expression artistique sous toutes ses formes. Théâtre, littérature, arts plastiques, musique ou créations pluridisciplinaires participent ainsi à faire dialoguer la tradition et l\'inspiration d\'aujourd\'hui.\", \"image\": \"accueil/accueil-chapelle-btwAqH.webp\", \"title\": \"Faire dialoguer la tradition et l\'inspiration d\'aujourd\'hui\", \"image_alt\": \"Chapelle baroque\"}, \"doors\": [{\"url\": \"/agenda?type=theatre\", \"color\": \"#1F4E8C\", \"title\": \"Théâtre\", \"subtitle\": \"Faire vivre le patrimoine religieux par le spectacle vivant.\"}, {\"url\": \"/agenda?type=conference\", \"color\": \"#B4492C\", \"title\": \"Conférences\", \"subtitle\": \"Histoire, art et spiritualité en miroir. Gratuites et ouvertes à tous.\"}, {\"url\": \"/agenda?type=visite\", \"color\": \"#2F5D50\", \"title\": \"Visites guidées\", \"subtitle\": \"Tout au long de l\'année, dans les églises des Alpes-Maritimes.\"}, {\"url\": \"/mot-cle/nuit-des-eglises\", \"color\": \"#152E47\", \"title\": \"Nuit des églises\", \"subtitle\": \"Une manifestation estivale, inscrite dans les diocèses depuis 2011.\"}], \"highlight\": {\"url\": \"/mot-cle/nuit-des-eglises\", \"text\": \"Initiée par l\'Église de France, une manifestation cultuelle et culturelle inscrite dans le paysage estival des diocèses depuis 2011, à laquelle participent chaque année plusieurs centaines d\'églises.\", \"image\": \"mots-cles/nuit-img-7899-wi8skz.webp\", \"label\": null, \"title\": \"Nuit des églises\", \"button\": \"Découvrir\", \"enabled\": true, \"image_alt\": \"Vitraux projetés sur la façade d\'une église, la nuit\"}}','2026-11-05 10:19:44','2026-11-05 10:41:31'),('legal','{\"privacy\": \"L\'Abside est responsable des données collectées sur ce site. Elles ne sont jamais vendues ni cédées à des tiers.\\n\\n## Inscription à un événement\\n\\nVotre nom, votre prénom, votre adresse e-mail, votre téléphone et la date choisie sont enregistrés pour organiser l\'événement et vous prévenir en cas de changement. Ils sont conservés *[durée à définir]*.\\n\\n## Formulaire de contact\\n\\nVotre nom, votre adresse e-mail et votre message nous sont transmis par e-mail afin de vous répondre. Ils ne sont pas conservés sur le site.\\n\\n## Lettre d\'information\\n\\nSi vous vous y inscrivez, votre prénom, votre nom et votre adresse e-mail sont enregistrés chez notre prestataire d\'e-mailing **Brevo** (Sendinblue SAS, France) pour vous envoyer nos informations. Chaque lettre contient un lien de désinscription.\\n\\n## Vos droits\\n\\nConformément au RGPD, vous disposez d\'un droit d\'accès, de rectification, d\'opposition et de suppression de vos données. Écrivez-nous à [abside.association@gmail.com](mailto:abside.association@gmail.com). Vous pouvez aussi adresser une réclamation à la [CNIL](https://www.cnil.fr).\", \"mentions\": \"## Éditeur du site\\n\\nL\'Abside, centre culturel et touristique catholique des Alpes-Maritimes (CCTC AM)\\nAssociation — SIREN 992 749 168\\n23 avenue Sévigné, 06100 Nice\\n\\nE-mail : [abside.association@gmail.com](mailto:abside.association@gmail.com) · Téléphone : 06 14 47 30 42\\n\\nDirecteur de la publication : *à compléter*\\n\\n## Hébergement\\n\\no2switch — SARL au capital de 100 000 €\\n222-224 Boulevard Gustave Flaubert, 63000 Clermont-Ferrand, France\\n[www.o2switch.fr](https://www.o2switch.fr)\\n\\n## Propriété intellectuelle\\n\\nLes textes, photographies, affiches et visuels de ce site sont la propriété de L\'Abside ou de leurs auteurs. Toute reproduction sans autorisation est interdite.\\n\\n## Données personnelles\\n\\nVoir notre [politique de confidentialité](/confidentialite).\"}','2026-11-05 10:19:44','2026-11-05 10:19:44'),('pages','{\"about\": {\"body\": \"À travers des expositions, visites, conférences, spectacles et événements ouverts à tous, L\'Abside met en lumière la richesse de l\'héritage architectural, artistique et spirituel du territoire.\\n\\nLieu de rencontre entre patrimoine, culture et création contemporaine, L\'Abside encourage également l\'expression artistique sous toutes ses formes. Théâtre, littérature, arts plastiques, musique ou créations pluridisciplinaires participent ainsi à faire dialoguer la tradition et l\'inspiration d\'aujourd\'hui.\\n\\n## Le Théâtre de l\'Abside\\n\\nLe Théâtre de l\'Abside est un projet culturel qui fait dialoguer création artistique et patrimoine religieux. À travers une programmation de spectacles vivants accueillis dans des édifices remarquables, il propose aux habitants une expérience culturelle originale tout en contribuant à la valorisation du patrimoine.\\n\\n## Les conférences\\n\\nNos conférences visent à transmettre la beauté de l\'art par une compréhension intégrale de l\'œuvre : histoire, art et spiritualité sont ainsi abordés en miroir. Les conférences sont gratuites et ouvertes à tous.\\n\\n## La Nuit des églises\\n\\nLa Nuit des églises, initiée par l\'Église de France, est une manifestation cultuelle et culturelle inscrite dans le paysage estival des diocèses depuis 2011, bénéficiant d\'une reconnaissance à l\'échelle nationale, et à laquelle participent chaque année plusieurs centaines d\'églises.\\n\\n## Les visites guidées\\n\\nTout au long de l\'année, des visites guidées gratuites et ouvertes à tous sont proposées dans les églises du département des Alpes-Maritimes.\", \"intro\": \"L\'Abside est un centre culturel et touristique dédié à la découverte du patrimoine religieux et culturel du diocèse de Nice.\", \"title\": \"Qui sommes-nous\"}, \"press\": {\"body\": \"Pour toute demande de la presse (visuels, interviews, dossier de presse), écrivez-nous à [abside.association@gmail.com](mailto:abside.association@gmail.com) ou appelez le 06 14 47 30 42.\", \"title\": \"Presse\"}, \"support\": {\"body\": \"*Texte à compléter : adhésion, dons, mécénat, bénévolat.*\", \"intro\": \"La plupart de nos événements sont en participation libre : votre soutien permet de les faire vivre.\", \"title\": \"Soutenir l\'association\"}}','2026-11-05 10:19:44','2026-11-05 10:19:44'),('site','{\"email\": \"abside.association@gmail.com\", \"phone\": \"06 14 47 30 42\", \"address\": \"23 avenue Sévigné, 06100 Nice\", \"legal_name\": \"L\'Abside, centre culturel et touristique catholique des Alpes-Maritimes\", \"description\": \"L\'Abside, centre culturel et touristique catholique des Alpes-Maritimes : théâtre, conférences, concerts, expositions et visites guidées dans les églises du diocèse de Nice.\", \"newsletter_text\": null, \"newsletter_title\": \"Recevoir les informations de L\'Abside\"}','2026-11-05 10:19:44','2026-11-05 10:19:44');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'editor',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `last_mcp_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrateur','bertrand.gorge@neayi.com',NULL,NULL,'admin',1,NULL,'2026-11-05 10:19:43','2026-11-05 10:19:43',NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_access_tokens` (
  `id` char(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `client_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scopes` text COLLATE utf8mb4_unicode_ci,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_access_tokens_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_auth_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_auth_codes` (
  `id` char(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `client_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scopes` text COLLATE utf8mb4_unicode_ci,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_auth_codes_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_clients` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `redirect_uris` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `grant_types` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_clients_owner_type_owner_id_index` (`owner_type`,`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_device_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_device_codes` (
  `id` char(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `client_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_code` char(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scopes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `user_approved_at` datetime DEFAULT NULL,
  `last_polled_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `oauth_device_codes_user_code_unique` (`user_code`),
  KEY `oauth_device_codes_user_id_index` (`user_id`),
  KEY `oauth_device_codes_client_id_index` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_refresh_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_refresh_tokens` (
  `id` char(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_token_id` char(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_refresh_tokens_access_token_id_index` (`access_token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `newsletter_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contacts_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `registrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint unsigned NOT NULL,
  `event_date_id` bigint unsigned DEFAULT NULL,
  `contact_id` bigint unsigned NOT NULL,
  `starts_at` datetime NOT NULL,
  `seats` smallint unsigned NOT NULL DEFAULT '1',
  `token` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reminded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `registrations_token_unique` (`token`),
  KEY `registrations_event_id_foreign` (`event_id`),
  KEY `registrations_contact_id_foreign` (`contact_id`),
  KEY `registrations_event_date_id_contact_id_index` (`event_date_id`,`contact_id`),
  KEY `registrations_starts_at_index` (`starts_at`),
  CONSTRAINT `registrations_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `registrations_event_date_id_foreign` FOREIGN KEY (`event_date_id`) REFERENCES `event_dates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `registrations_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

SET FOREIGN_KEY_CHECKS=1;
