/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.4.10-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: u228744577_pos
-- ------------------------------------------------------
-- Server version	11.4.10-MariaDB-ubu2404

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `_product_id_map`
--

DROP TABLE IF EXISTS `_product_id_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_product_id_map` (
  `old_id` int(11) NOT NULL,
  `canonical_id` int(11) NOT NULL,
  PRIMARY KEY (`old_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_product_id_map`
--

LOCK TABLES `_product_id_map` WRITE;
/*!40000 ALTER TABLE `_product_id_map` DISABLE KEYS */;
INSERT INTO `_product_id_map` VALUES
(12,12),
(13,12),
(14,14),
(15,14),
(16,16),
(17,16),
(18,18),
(19,18),
(21,21),
(22,16),
(23,23),
(24,23),
(25,23),
(26,23);
/*!40000 ALTER TABLE `_product_id_map` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id_admin` int(11) NOT NULL AUTO_INCREMENT,
  `email_admin` text DEFAULT NULL,
  `password_admin` text DEFAULT NULL,
  `rol_admin` text DEFAULT NULL,
  `permissions_admin` text DEFAULT NULL,
  `token_admin` text DEFAULT NULL,
  `token_exp_admin` text DEFAULT NULL,
  `status_admin` int(11) DEFAULT 1,
  `title_admin` text DEFAULT NULL,
  `symbol_admin` text DEFAULT NULL,
  `font_admin` text DEFAULT NULL,
  `color_admin` text DEFAULT NULL,
  `back_admin` text DEFAULT NULL,
  `scode_admin` text DEFAULT NULL,
  `name_admin` text DEFAULT NULL,
  `id_office_admin` int(11) DEFAULT 0,
  `chatgpt_admin` text DEFAULT NULL,
  `date_created_admin` date DEFAULT NULL,
  `date_updated_admin` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `surname_admin` text DEFAULT NULL,
  `img_admin` text DEFAULT NULL,
  `type_seller` varchar(50) DEFAULT 'cajero',
  `id_warehouse_admin` int(11) DEFAULT 0,
  `id_inventory_admin` int(11) DEFAULT 0,
  `pct_commission_admin` double DEFAULT 0,
  PRIMARY KEY (`id_admin`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES
(1,'superadmin@pos.com','$2a$07$azybxcags23425sdg23sdeanQZqjaf6Birm2NvcYTNtJw24CsO5uq','superadmin','{\"todo\":\"on\"}','eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NjI1NzY2NTksImV4cCI6MTc2MjY2MzA1OSwiZGF0YSI6eyJpZCI6MSwiZW1haWwiOiJzdXBlcmFkbWluQHBvcy5jb20ifX0.vcz7mqyXQLnohiRAtUEmTZIY3JfLmBZKp6sMetioOKs','1762663059',1,'POS SYSTEM','<i class=\"bi bi-cart-check-fill\"></i>','<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\"><link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin><link href=\"https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap\" rel=\"stylesheet\">','#611be4','http://cms.pos.com/views/assets/files/6760a08e6d34e6.png','w958zu','El Programador',0,'{\"token\":\"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\",\"org\":\"\"}','2024-12-16','2025-11-08 04:37:39',NULL,NULL,'cajero',0,0,0),
(13,'marisol@jebolivia.com.bo','$2a$07$azybxcags23425sdg23sdeanQZqjaf6Birm2NvcYTNtJw24CsO5uq','admin','%7B%22archivos%22%3A%22%22%2C%22clientes%22%3A%22%22%2C%22categorias%22%3A%22%22%2C%22productos%22%3A%22%22%2C%22compras%22%3A%22%22%2C%22ordenes%22%3A%22%22%2C%22gastos%22%3A%22%22%2C%22informes%22%3A%22%22%7D','eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NjI1NDg5NjAsImV4cCI6MTc2MjYzNTM2MCwiZGF0YSI6eyJpZCI6MTMsImVtYWlsIjoibWFyaXNvbEBqZWJvbGl2aWEuY29tLmJvIn19.x1GFb_wQYg1QYX_j4g0o61IwHbAD8G18mCZNEvW_FCE','1762635360',1,'','','','','','','Marisol+Silva',3,NULL,'2025-10-25','2025-11-07 20:56:00',NULL,NULL,'cajero',0,0,0),
(14,'caja@pos.com','$2a$07$azybxcags23425sdg23sdeuiZyE5TxUkrcXrZtPfYBFC6APznfwgC','cajero','{\"pos\":\"on\",\"sucursales\":\"off\",\"admins\":\"off\",\"clientes\":\"on\",\"categorias\":\"off\",\"productos\":\"off\",\"combos\":\"off\",\"compras\":\"off\",\"ordenes\":\"on\",\"ventas\":\"off\",\"caja\":\"on\",\"gastos\":\"on\",\"proveedores\":\"off\",\"almacenes\":\"off\",\"almacen\":\"off\",\"despachos\":\"off\",\"mi_inventario\":\"on\",\"solicitar_inventario\":\"off\",\"reportes\":\"off\",\"reportes_empresa\":\"off\",\"dashboard_lab\":\"off\",\"materiales\":\"off\",\"insumos_lab\":\"off\",\"inventario_mp\":\"off\",\"entradas\":\"off\",\"recetas\":\"off\",\"produccion\":\"off\",\"calidad\":\"off\",\"inventario_final\":\"off\"}','eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3ODExOTkzOTYsImV4cCI6MTc4MTI4NTc5NiwiZGF0YSI6eyJpZCI6MTQsImVtYWlsIjoiY2FqYUBwb3MuY29tIn19.hR-IbxdS5DoTr7rZnLBVonSvBC7XQOVbmcB5z6fRAhA','1781285796',1,'','','','','','','Charito - Caja',3,NULL,'2025-10-25','2026-06-11 17:36:36','-',NULL,'cajero',0,0,0),
(15,'admin@pos.com','$2a$07$azybxcags23425sdg23sdeanQZqjaf6Birm2NvcYTNtJw24CsO5uq','admin','%7B%7D','eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3ODEyMDE4NzgsImV4cCI6MTc4MTI4ODI3OCwiZGF0YSI6eyJpZCI6MTUsImVtYWlsIjoiYWRtaW5AcG9zLmNvbSJ9fQ.1Qot1KK-yAKDN3bQuAqq3sqPBFUOPzKPfmMUeQ8iK6o','1781288278',1,'','','','','','','Admin',3,NULL,'2025-11-08','2026-06-11 18:17:58',NULL,NULL,'cajero',0,0,0),
(16,'vendedor1@pos.com','','vendedor',NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'Vendedor',3,NULL,NULL,'2026-06-08 03:19:10','Uno',NULL,'cajero',0,0,5),
(17,'despacho@pos.com','','despachador',NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'Despachador',1,NULL,NULL,'2026-06-08 03:19:10','Central',NULL,'cajero',0,0,0),
(18,'t@t.com','12345678','cajero','{}',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'T',0,NULL,NULL,'2026-06-08 21:07:56','T',NULL,'cajero',0,0,0),
(19,'shebi@pos.com','$2y$10$dV58/BGT3UEW8sn.HujWFuxD/h.CN.3OwB6sqdeT80xcCKuetAcka','lab_admin','{\"pos\":\"off\",\"sucursales\":\"off\",\"admins\":\"off\",\"clientes\":\"off\",\"categorias\":\"off\",\"productos\":\"off\",\"combos\":\"off\",\"compras\":\"off\",\"ordenes\":\"off\",\"ventas\":\"off\",\"caja\":\"off\",\"gastos\":\"off\",\"proveedores\":\"off\",\"almacenes\":\"off\",\"almacen\":\"on\",\"despachos\":\"off\",\"mi_inventario\":\"on\",\"solicitar_inventario\":\"off\",\"reportes\":\"off\",\"reportes_empresa\":\"off\",\"dashboard_lab\":\"on\",\"materiales\":\"on\",\"insumos_lab\":\"on\",\"inventario_mp\":\"on\",\"entradas\":\"on\",\"recetas\":\"on\",\"produccion\":\"on\",\"calidad\":\"on\",\"inventario_final\":\"on\"}','eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3ODExODY5OTQsImV4cCI6MTc4MTI3MzM5NCwiZGF0YSI6eyJpZCI6MTksImVtYWlsIjoic2hlYmlAcG9zLmNvbSJ9fQ.QZ6Bkg7QYnhG2JU3ecTcZJMXL9qHDeLJ4i1uR7i-c7I','1781273394',1,NULL,NULL,NULL,NULL,NULL,NULL,'shebi',0,NULL,NULL,'2026-06-11 14:09:54','lopez',NULL,'cajero',0,0,0),
(20,'sebas@pos.com','$2y$10$/WIX1udogCxIaq3l7dAyau1RFw1dKzOKew0keCSpWYGuPZlBzub5a','cajero','{\"pos\":\"on\",\"sucursales\":\"off\",\"admins\":\"off\",\"clientes\":\"off\",\"categorias\":\"off\",\"productos\":\"on\",\"combos\":\"off\",\"compras\":\"off\",\"ordenes\":\"on\",\"ventas\":\"on\",\"caja\":\"on\",\"gastos\":\"on\",\"proveedores\":\"off\",\"almacenes\":\"off\",\"almacen\":\"off\",\"despachos\":\"off\",\"mi_inventario\":\"on\",\"solicitar_inventario\":\"on\",\"reportes\":\"on\",\"reportes_empresa\":\"off\",\"dashboard_lab\":\"off\",\"materiales\":\"off\",\"insumos_lab\":\"off\",\"inventario_mp\":\"off\",\"entradas\":\"off\",\"recetas\":\"off\",\"produccion\":\"off\",\"calidad\":\"off\",\"inventario_final\":\"off\"}','eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3ODExOTMwMDcsImV4cCI6MTc4MTI3OTQwNywiZGF0YSI6eyJpZCI6MjAsImVtYWlsIjoic2ViYXNAcG9zLmNvbSJ9fQ.N17fE_RFtylZYKBVFZsu7KXg3cADr8A1K-t4JSdC3Qw','1781279407',1,NULL,NULL,NULL,NULL,NULL,NULL,'sebastian',7,NULL,NULL,'2026-06-11 15:50:07','guzman',NULL,'cajero',0,7,0),
(21,'guille@pos.com','$2y$10$5R6aGvpQwU3albPbVtVyAem3m.AZQXjKKtCT2OU/syVM1ZN/9/Jmq','despachador','{\"pos\":\"off\",\"sucursales\":\"off\",\"admins\":\"off\",\"clientes\":\"off\",\"categorias\":\"off\",\"productos\":\"on\",\"combos\":\"off\",\"compras\":\"on\",\"ordenes\":\"off\",\"ventas\":\"off\",\"caja\":\"off\",\"gastos\":\"off\",\"proveedores\":\"on\",\"almacenes\":\"off\",\"almacen\":\"on\",\"despachos\":\"off\",\"mi_inventario\":\"on\",\"solicitar_inventario\":\"off\",\"reportes\":\"off\",\"reportes_empresa\":\"off\",\"dashboard_lab\":\"off\",\"materiales\":\"off\",\"insumos_lab\":\"off\",\"inventario_mp\":\"off\",\"entradas\":\"off\",\"recetas\":\"off\",\"produccion\":\"off\",\"calidad\":\"off\",\"inventario_final\":\"off\"}','eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3ODExOTE2MzksImV4cCI6MTc4MTI3ODAzOSwiZGF0YSI6eyJpZCI6MjEsImVtYWlsIjoiZ3VpbGxlQHBvcy5jb20ifX0.im6X5-TUZD7-brAonmSsyHdQf6pYsnYiJfuSxCVHaYw','1781278039',1,NULL,NULL,NULL,NULL,NULL,NULL,'guillerme',0,NULL,NULL,'2026-06-11 15:27:19','da silva',NULL,'cajero',2,0,0);
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bills`
--

DROP TABLE IF EXISTS `bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bills` (
  `id_bill` int(11) NOT NULL AUTO_INCREMENT,
  `concept_bill` text DEFAULT NULL,
  `cost_bill` double DEFAULT 0,
  `date_bill` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_admin_bill` int(11) DEFAULT 0,
  `id_office_bill` int(11) DEFAULT 0,
  `date_created_bill` date DEFAULT NULL,
  `date_updated_bill` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_cash_bill` int(11) DEFAULT 0,
  PRIMARY KEY (`id_bill`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bills`
--

LOCK TABLES `bills` WRITE;
/*!40000 ALTER TABLE `bills` DISABLE KEYS */;
INSERT INTO `bills` VALUES
(4,'Fotocopias',10,'2024-12-28 01:03:00',1,3,'2024-12-27','2025-11-04 05:11:27',0),
(5,'Cinta',10,'2025-10-22 13:03:00',1,3,'2025-10-22','2025-11-04 05:11:07',0),
(7,'Fotocopias',5.5,'2025-11-07 17:03:00',13,3,'2025-11-07','2025-11-07 21:19:01',0),
(8,'Fotocopias',3.5,'2025-11-08 00:03:00',14,3,'2025-11-08','2025-11-08 04:53:39',0),
(9,'Cinta',10,'2025-11-08 00:03:00',1,3,'2025-11-08','2025-11-08 04:59:53',0),
(10,'Fotocopia',20,'2025-11-08 01:03:00',1,3,'2025-11-08','2025-11-08 05:15:17',0),
(11,'Prueba',5,'2025-11-08 01:03:00',1,3,'2025-11-08','2025-11-08 05:16:56',0),
(12,'Prueba',5,'2025-11-08 01:03:00',1,3,'2025-11-08','2025-11-08 05:26:31',0),
(13,'test',15,'2026-06-05 20:05:42',1,3,NULL,'2026-06-05 20:05:42',1),
(14,'prueba',999,'2026-06-05 20:10:49',15,3,NULL,'2026-06-05 20:10:49',22),
(15,'a',999,'2026-06-05 20:11:13',15,3,NULL,'2026-06-05 20:11:13',23),
(16,'1',1,'2026-06-10 02:05:29',15,3,NULL,'2026-06-10 02:05:29',27),
(17,'comida',100,'2026-06-11 15:14:24',20,7,NULL,'2026-06-11 15:14:24',28),
(18,'comida',140,'2026-06-11 15:47:49',20,7,NULL,'2026-06-11 15:47:49',28),
(19,'pago',50,'2026-06-11 16:06:27',20,7,NULL,'2026-06-11 16:06:27',29);
/*!40000 ALTER TABLE `bills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cashs`
--

DROP TABLE IF EXISTS `cashs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashs` (
  `id_cash` int(11) NOT NULL AUTO_INCREMENT,
  `start_cash` double DEFAULT 0,
  `bills_cash` double DEFAULT 0,
  `money_cash` double DEFAULT 0,
  `diff_cash` double DEFAULT 0,
  `end_cash` double DEFAULT 0,
  `gap_cash` double DEFAULT 0,
  `status_cash` int(11) DEFAULT 1,
  `date_start_cash` datetime DEFAULT NULL,
  `date_end_cash` datetime DEFAULT NULL,
  `id_admin_cash` int(11) DEFAULT 0,
  `id_office_cash` int(11) DEFAULT 0,
  `date_created_cash` date DEFAULT NULL,
  `date_updated_cash` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cash_efectivo` double DEFAULT 0,
  `cash_qr` double DEFAULT 0,
  PRIMARY KEY (`id_cash`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cashs`
--

LOCK TABLES `cashs` WRITE;
/*!40000 ALTER TABLE `cashs` DISABLE KEYS */;
INSERT INTO `cashs` VALUES
(8,250,0,1295,1545,1545,0,0,'2025-10-31 19:03:00','2025-10-31 22:03:00',1,3,'2025-10-31','2025-11-01 02:08:04',0,0),
(9,350,0,155,505,505,0,0,'2025-11-03 23:03:00','2025-11-04 00:03:00',13,3,'2025-11-03','2025-11-04 04:09:18',0,0),
(10,315,0,0,315,315,0,0,'2025-11-04 00:03:00','2025-11-04 01:03:00',1,3,'2025-11-04','2025-11-04 05:08:26',0,0),
(15,674.5,10,0,0,0,0,0,'2025-11-04 15:03:00','2025-11-04 17:03:00',13,3,'2025-11-04','2025-11-07 20:38:33',0,0),
(16,651.5,20,0,0,0,0,0,'2025-11-07 17:03:00','2025-11-07 19:03:00',13,3,'2025-11-07','2025-11-07 21:05:46',0,0),
(17,312.5,0,0,312.5,312.5,0,0,'2025-11-07 17:03:00','2025-11-07 21:03:00',13,3,'2025-11-07','2025-11-08 04:38:57',0,0),
(18,450,0,0,450,450,0,0,'2025-11-08 00:03:00','2025-11-08 02:03:00',14,3,'2025-11-08','2026-06-05 20:01:22',0,0),
(19,351.5,-3.5,340,688,688,0,0,'2025-11-08 00:03:00','2025-11-08 04:03:00',14,3,'2025-11-08','2025-11-08 04:59:05',0,0),
(21,150,0,0,150,150,0,0,'0000-00-00 00:00:00','2026-06-11 16:04:43',1,0,'2025-11-08','2026-06-11 16:04:43',0,0),
(24,200,1,1,1,1,1,1,NULL,NULL,1,4,NULL,'2026-06-08 04:30:59',0,0),
(26,100,0,0,100,100,11,0,NULL,'2026-06-08 20:07:23',15,3,NULL,'2026-06-08 20:07:23',0,0),
(27,111,1,0,110,110,1,1,NULL,NULL,14,3,NULL,'2026-06-11 18:02:32',4852,749),
(28,150,240,135,45,45,0,0,'2026-06-11 11:12:16','2026-06-11 16:05:07',20,7,'2026-06-11','2026-06-11 16:07:10',90,0),
(29,400,50,45,395,395,0,0,'2026-06-11 12:05:31','2026-06-11 16:07:53',20,7,'2026-06-11','2026-06-11 16:07:53',135,0);
/*!40000 ALTER TABLE `cashs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id_category` int(11) NOT NULL AUTO_INCREMENT,
  `title_category` text DEFAULT NULL,
  `img_category` text DEFAULT NULL,
  `order_category` int(11) DEFAULT 0,
  `status_category` int(11) DEFAULT 1,
  `date_created_category` date DEFAULT NULL,
  `date_updated_category` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_category`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(8,'Vinagres','https%3A%2F%2Fpos.desarrolloweb24siete.com%2F%2Fviews%2Fassets%2Ffiles%2F69025436db56e50.png',0,1,'2025-10-29','2025-10-29 19:44:39'),
(9,'Tonicos','https://pos.desarrolloweb24siete.com//views/assets/files/69025436db56e50.png',0,1,'2025-10-29','2025-10-29 19:45:27'),
(10,'Vital+Herbs','',0,1,'2025-10-29','2025-10-29 20:39:56'),
(12,'asad','',0,1,NULL,'2026-06-10 02:02:26');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id_client` int(11) NOT NULL AUTO_INCREMENT,
  `dni_client` text DEFAULT NULL,
  `name_client` text DEFAULT NULL,
  `surname_client` text DEFAULT NULL,
  `email_client` text DEFAULT NULL,
  `address_client` text DEFAULT NULL,
  `phone_client` text DEFAULT NULL,
  `id_office_client` int(11) DEFAULT 0,
  `date_created_client` date DEFAULT NULL,
  `date_updated_client` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nit_client` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_client`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` VALUES
(14,'13334410','Andres','Fernandez','andres@gmail.com','La+Pradera','60836039',3,'2025-10-25','2025-10-25 23:10:04',NULL),
(15,'2433154','Norma','Jaldin','norma@gmail.com','La Pradera','79005900',3,'2025-10-25','2025-10-25 23:16:57',NULL),
(16,'2324033','Federico Enrique','Fernandez Pabon','federico@gmail.com','Condominio La Pradera zona norte','70837024',4,'2025-10-30','2025-10-31 03:26:37',NULL),
(18,'6625123','Daniel','Fernandez','daniel@gmail.com','Sevilla+Terrazas','79555012',3,'2025-11-08','2025-11-08 04:49:23',NULL),
(19,'11111111','se','bas','1212@gmail.com','dasd1','111111111',5,NULL,'2026-06-10 02:00:50',NULL);
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `columns`
--

DROP TABLE IF EXISTS `columns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `columns` (
  `id_column` int(11) NOT NULL AUTO_INCREMENT,
  `id_module_column` int(11) DEFAULT 0,
  `title_column` text DEFAULT NULL,
  `alias_column` text DEFAULT NULL,
  `type_column` text DEFAULT NULL,
  `matrix_column` text DEFAULT NULL,
  `visible_column` int(11) DEFAULT 1,
  `date_created_column` date DEFAULT NULL,
  `date_updated_column` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_column`)
) ENGINE=InnoDB AUTO_INCREMENT=140 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `columns`
--

LOCK TABLES `columns` WRITE;
/*!40000 ALTER TABLE `columns` DISABLE KEYS */;
INSERT INTO `columns` VALUES
(1,2,'rol_admin','rol','select','superadmin,admin,editor',1,'2024-12-16','2025-10-26 00:02:08'),
(2,2,'permissions_admin','permisos','object','',1,'2024-12-16','2024-12-16 21:46:24'),
(3,2,'email_admin','email','email','',1,'2024-12-16','2024-12-16 21:46:24'),
(4,2,'password_admin','pass','password','',0,'2024-12-16','2024-12-16 21:46:24'),
(5,2,'token_admin','token','text','',0,'2024-12-16','2024-12-16 21:46:24'),
(6,2,'token_exp_admin','expiración','text','',0,'2024-12-16','2024-12-16 21:46:24'),
(7,2,'status_admin','estado','boolean','',1,'2024-12-16','2024-12-16 21:46:24'),
(8,2,'title_admin','título','text','',0,'2024-12-16','2024-12-16 21:46:25'),
(9,2,'symbol_admin','simbolo','text','',0,'2024-12-16','2024-12-16 21:46:25'),
(10,2,'font_admin','tipografía','text','',0,'2024-12-16','2024-12-16 21:46:25'),
(11,2,'color_admin','color','text','',0,'2024-12-16','2024-12-16 21:46:25'),
(12,2,'back_admin','fondo','text','',0,'2024-12-16','2024-12-16 21:46:25'),
(13,2,'scode_admin','seguridad','text','',0,'2024-12-16','2024-12-16 21:46:25'),
(14,4,'title_office','Sucursales','text',NULL,1,'2024-12-17','2024-12-16 23:17:24'),
(15,4,'address_office','Dirección','text',NULL,1,'2024-12-17','2024-12-16 23:17:24'),
(16,4,'phone_office','Teléfono','text',NULL,1,'2024-12-17','2024-12-16 23:17:24'),
(17,6,'dni_client','Documento','text',NULL,1,'2024-12-18','2024-12-18 19:37:40'),
(18,6,'name_client','Nombre','text',NULL,1,'2024-12-18','2024-12-18 19:37:40'),
(19,6,'surname_client','Apellido','text',NULL,1,'2024-12-18','2024-12-18 19:37:40'),
(20,6,'email_client','Email','email',NULL,1,'2024-12-18','2024-12-18 19:37:40'),
(21,6,'address_client','Dirección','text',NULL,1,'2024-12-18','2024-12-18 19:37:41'),
(22,6,'phone_client','Teléfono','text',NULL,1,'2024-12-18','2024-12-18 19:37:41'),
(23,6,'id_office_client','Sucursal','relations','offices',1,'2024-12-18','2024-12-18 19:38:33'),
(24,8,'title_category','Categoría','text',NULL,1,'2024-12-18','2024-12-18 20:14:59'),
(25,8,'img_category','Imagen','image',NULL,1,'2024-12-18','2024-12-18 20:15:00'),
(26,8,'order_category','Orden','order',NULL,1,'2024-12-18','2024-12-18 20:15:00'),
(27,8,'status_category','Estado','boolean',NULL,1,'2024-12-18','2024-12-18 20:15:00'),
(28,10,'title_product','Producto','text',NULL,1,'2024-12-18','2024-12-18 20:38:31'),
(29,10,'img_product','Imagen','image',NULL,1,'2024-12-18','2024-12-18 20:38:31'),
(30,10,'id_category_product','Categoría','relations','categories',1,'2024-12-18','2024-12-18 20:42:20'),
(31,10,'sku_product','SKU','text',NULL,1,'2024-12-18','2024-12-18 20:38:32'),
(32,10,'unit_product','Medida','select','1Lt,2Lt,500gr,50gr',1,'2024-12-18','2025-11-08 04:51:03'),
(35,10,'stock_product','Stock','text',NULL,1,'2024-12-18','2025-10-29 22:14:18'),
(36,10,'discount_product','Descuento en %','double',NULL,1,'2024-12-18','2025-10-29 19:49:54'),
(37,10,'status_product','Estado','boolean',NULL,1,'2024-12-18','2024-12-18 20:38:33'),
(38,10,'id_office_product','Sucursal','relations','offices',1,'2024-12-18','2025-10-31 22:18:15'),
(48,14,'transaction_order','Transacción','pos',NULL,1,'2024-12-18','2024-12-28 00:49:38'),
(49,14,'id_admin_order','Vendedor','relations','admins',1,'2024-12-18','2024-12-18 22:41:54'),
(50,14,'id_client_order','Cliente','relations','clients',1,'2024-12-18','2024-12-18 22:42:03'),
(51,14,'subtotal_order','Subtotal','money',NULL,1,'2024-12-18','2024-12-18 22:41:11'),
(52,14,'discount_order','Descuento','money',NULL,1,'2024-12-18','2024-12-18 22:41:11'),
(53,14,'tax_order','Impuesto','money',NULL,1,'2024-12-18','2024-12-18 22:41:12'),
(54,14,'total_order','Total','money',NULL,1,'2024-12-18','2024-12-18 22:41:12'),
(55,14,'method_order','Método','select','efectivo,transferencia,tarjeta',1,'2024-12-18','2024-12-18 22:46:09'),
(56,14,'transfer_order','Transferencia','text',NULL,1,'2024-12-18','2024-12-18 22:41:12'),
(57,14,'status_order','Estado','select','Completada,Pendiente',1,'2024-12-18','2024-12-18 22:46:26'),
(58,14,'date_order','Fecha','timestamp',NULL,1,'2024-12-18','2024-12-18 22:41:13'),
(59,14,'id_office_order','Sucursal','relations','offices',1,'2024-12-18','2024-12-18 22:42:12'),
(60,16,'id_order_sale','Orden','relations','orders',1,'2024-12-18','2024-12-18 22:55:22'),
(61,16,'id_product_sale','Producto','relations','products',1,'2024-12-18','2024-12-18 22:55:18'),
(62,16,'tax_type_sale','Tipo Impuesto','text',NULL,1,'2024-12-18','2024-12-18 22:54:25'),
(63,16,'tax_sale','Impuesto','double',NULL,1,'2024-12-18','2025-02-15 00:13:12'),
(64,16,'discount_sale','Descuento','double',NULL,1,'2024-12-18','2025-02-15 00:13:12'),
(65,16,'qty_sale','Cantidad','int',NULL,1,'2024-12-18','2024-12-18 22:54:25'),
(66,16,'subtotal_sale','Subtotal','money',NULL,1,'2024-12-18','2024-12-18 22:54:26'),
(67,16,'status_sale','Estado','select','Completada,Pendiente',1,'2024-12-18','2024-12-18 22:55:10'),
(68,16,'id_admin_sale','Vendedor','relations','admins',1,'2024-12-18','2024-12-18 22:55:01'),
(69,16,'id_client_sale','Cliente','relations','clients',1,'2024-12-18','2024-12-18 22:54:56'),
(70,16,'id_office_sale','Sucursal','relations','offices',1,'2024-12-18','2024-12-18 22:54:49'),
(71,18,'start_cash','Dinero Inicial','money',NULL,1,'2024-12-19','2024-12-18 23:09:25'),
(72,18,'bills_cash','Gastos','money',NULL,1,'2024-12-19','2024-12-18 23:09:26'),
(73,18,'money_cash','Ingresos','money',NULL,1,'2024-12-19','2024-12-18 23:09:26'),
(74,18,'diff_cash','Diferencia','money',NULL,1,'2024-12-19','2024-12-18 23:09:26'),
(75,18,'end_cash','Dinero Final','money',NULL,1,'2024-12-19','2024-12-18 23:09:26'),
(76,18,'gap_cash','Brecha','money',NULL,1,'2024-12-19','2024-12-18 23:09:27'),
(77,18,'status_cash','Estado','boolean',NULL,1,'2024-12-19','2024-12-18 23:09:27'),
(78,18,'date_start_cash','Fecha Inicial','datetime',NULL,1,'2024-12-19','2024-12-18 23:09:27'),
(79,18,'date_end_cash','Fecha Final','datetime',NULL,1,'2024-12-19','2024-12-18 23:09:27'),
(80,18,'id_admin_cash','Administrador','relations','admins',1,'2024-12-19','2024-12-18 23:09:43'),
(81,18,'id_office_cash','Sucursal','relations','offices',1,'2024-12-19','2024-12-18 23:09:39'),
(82,20,'concept_bill','Concepto','text',NULL,1,'2024-12-19','2024-12-18 23:14:38'),
(83,20,'cost_bill','Costo','money',NULL,1,'2024-12-19','2024-12-18 23:14:38'),
(84,20,'date_bill','Fecha','timestamp',NULL,1,'2024-12-19','2024-12-18 23:14:39'),
(85,20,'id_admin_bill','Administrador','relations','admins',1,'2024-12-19','2024-12-19 15:48:06'),
(86,20,'id_office_bill','Sucursal','relations','offices',1,'2024-12-19','2024-12-19 15:55:46'),
(87,2,'name_admin','Nombre','text',NULL,1,'2024-12-19','2024-12-19 20:12:24'),
(88,2,'id_office_admin','Sucursal','relations','offices',1,'2024-12-19','2024-12-19 20:20:36'),
(89,10,'code_product','Código de Barras','text',NULL,1,'2025-01-24','2025-01-24 13:13:35'),
(90,4,'dni_office','NIT','text',NULL,1,'2025-01-24','2025-01-24 15:57:02'),
(102,40,'supplier_name','Proveedor','text',NULL,1,'2025-10-29','2025-10-29 20:02:41'),
(103,40,'supplier_contact','Contácto','text',NULL,1,'2025-10-29','2025-10-29 20:02:41'),
(115,41,'id_supplier_purchase','Proveedor','relations','suppliers',1,'2025-10-31','2025-10-31 23:07:32'),
(116,41,'id_office_purchase','Almac??n','relations','warehouses',1,'2025-10-31','2026-06-11 13:40:35'),
(117,41,'id_product_purchase','Producto','relations','products',1,'2025-10-31','2025-10-31 23:07:49'),
(118,41,'cost_purchase','Costo producto','money',NULL,1,'2025-10-31','2025-10-31 23:07:11'),
(119,41,'utility_purchase','Utilidad %','select','100%,50%,150%,200%',1,'2025-10-31','2025-11-08 04:52:08'),
(120,41,'price_purchase','Precio venta','money',NULL,1,'2025-10-31','2025-10-31 23:07:11'),
(121,41,'qty_purchase','Cantidad','int',NULL,1,'2025-10-31','2025-10-31 23:07:11'),
(122,41,'invest_purchase','Inversión','money',NULL,1,'2025-10-31','2025-10-31 23:07:11'),
(123,42,'concept_income','Concepto','text','',1,'2026-06-05','2026-06-05 18:18:07'),
(124,43,'concept_income','Concepto','text','',1,'2026-06-05','2026-06-05 18:18:07'),
(125,42,'amount_income','Monto','money','',1,'2026-06-05','2026-06-05 18:18:07'),
(126,43,'amount_income','Monto','money','',1,'2026-06-05','2026-06-05 18:18:07'),
(127,42,'date_income','Fecha','timestamp','',1,'2026-06-05','2026-06-05 18:18:07'),
(128,43,'date_income','Fecha','timestamp','',1,'2026-06-05','2026-06-05 18:18:07'),
(129,42,'id_cash_income','Caja','text','',1,'2026-06-05','2026-06-05 18:18:07'),
(130,43,'id_cash_income','Caja','text','',1,'2026-06-05','2026-06-05 18:18:07'),
(131,42,'id_admin_income','Administrador','relations','admins',1,'2026-06-05','2026-06-05 18:18:07'),
(132,43,'id_admin_income','Administrador','relations','admins',1,'2026-06-05','2026-06-05 18:18:07'),
(133,42,'id_office_income','Sucursal','relations','offices',1,'2026-06-05','2026-06-05 18:18:07'),
(134,43,'id_office_income','Sucursal','relations','offices',1,'2026-06-05','2026-06-05 18:18:07'),
(135,20,'id_cash_bill','Caja','text','',1,'2026-06-05','2026-06-05 18:18:07'),
(136,44,'title_warehouse','Nombre','text',NULL,1,'2026-06-11','2026-06-11 13:39:55'),
(137,44,'address_warehouse','Direccion','text',NULL,1,'2026-06-11','2026-06-11 14:09:43'),
(138,44,'phone_warehouse','Telefono','text',NULL,1,'2026-06-11','2026-06-11 14:09:43'),
(139,2,'id_warehouse_admin','Almacen Asignado','relations','warehouses',1,'2026-06-11','2026-06-11 13:39:55');
/*!40000 ALTER TABLE `columns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `combo_items`
--

DROP TABLE IF EXISTS `combo_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `combo_items` (
  `id_combo_item` int(11) NOT NULL AUTO_INCREMENT,
  `id_combo_ci` int(11) NOT NULL,
  `id_product_ci` int(11) NOT NULL,
  `qty_ci` int(11) NOT NULL DEFAULT 1,
  `date_created_ci` date DEFAULT NULL,
  `date_updated_ci` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_combo_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `combo_items`
--

LOCK TABLES `combo_items` WRITE;
/*!40000 ALTER TABLE `combo_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `combo_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consignment_items`
--

DROP TABLE IF EXISTS `consignment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `consignment_items` (
  `id_consignment_item` int(11) NOT NULL AUTO_INCREMENT,
  `id_consignment` int(11) NOT NULL,
  `id_product_consignment` int(11) NOT NULL,
  `qty_assigned` int(11) NOT NULL DEFAULT 0,
  `price_consignment` double NOT NULL DEFAULT 0,
  `qty_sold` int(11) DEFAULT 0,
  `qty_returned` int(11) DEFAULT 0,
  `date_created_item` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_consignment_item`),
  KEY `idx_consignit_cons` (`id_consignment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consignment_items`
--

LOCK TABLES `consignment_items` WRITE;
/*!40000 ALTER TABLE `consignment_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `consignment_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consignments`
--

DROP TABLE IF EXISTS `consignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `consignments` (
  `id_consignment` int(11) NOT NULL AUTO_INCREMENT,
  `id_admin_consignment` int(11) NOT NULL,
  `id_office_consignment` int(11) NOT NULL,
  `notes_consignment` text DEFAULT NULL,
  `date_created_consignment` date DEFAULT NULL,
  `status_consignment` varchar(50) DEFAULT 'pendiente',
  `file_consignment` varchar(255) DEFAULT NULL,
  `reference_consignment` varchar(255) DEFAULT NULL,
  `date_updated_consignment` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_consignment`),
  KEY `idx_consign_office` (`id_office_consignment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consignments`
--

LOCK TABLES `consignments` WRITE;
/*!40000 ALTER TABLE `consignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `consignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credit_payments`
--

DROP TABLE IF EXISTS `credit_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_payments` (
  `id_payment` int(11) NOT NULL AUTO_INCREMENT,
  `id_credit_payment` int(11) NOT NULL,
  `amount_payment` double NOT NULL DEFAULT 0,
  `method_payment` varchar(50) DEFAULT 'efectivo',
  `reference_payment` varchar(255) DEFAULT NULL,
  `file_payment` varchar(255) DEFAULT NULL,
  `id_admin_payment` int(11) NOT NULL DEFAULT 0,
  `date_created_payment` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_payment`),
  KEY `idx_creditpay_credit` (`id_credit_payment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_payments`
--

LOCK TABLES `credit_payments` WRITE;
/*!40000 ALTER TABLE `credit_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credits`
--

DROP TABLE IF EXISTS `credits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credits` (
  `id_credit` int(11) NOT NULL AUTO_INCREMENT,
  `id_client_credit` int(11) NOT NULL,
  `id_office_credit` int(11) NOT NULL,
  `id_admin_credit` int(11) NOT NULL,
  `amount_credit` double NOT NULL DEFAULT 0,
  `balance_credit` double NOT NULL DEFAULT 0,
  `due_date_credit` date DEFAULT NULL,
  `notes_credit` text DEFAULT NULL,
  `status_credit` varchar(50) DEFAULT 'activo',
  `date_created_credit` date DEFAULT NULL,
  `date_updated_credit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_credit`),
  KEY `idx_credits_client` (`id_client_credit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credits`
--

LOCK TABLES `credits` WRITE;
/*!40000 ALTER TABLE `credits` DISABLE KEYS */;
/*!40000 ALTER TABLE `credits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `files`
--

DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `files` (
  `id_file` int(11) NOT NULL AUTO_INCREMENT,
  `id_folder_file` int(11) DEFAULT 0,
  `name_file` text DEFAULT NULL,
  `extension_file` text DEFAULT NULL,
  `type_file` text DEFAULT NULL,
  `size_file` double DEFAULT 0,
  `link_file` text DEFAULT NULL,
  `thumbnail_vimeo_file` text DEFAULT NULL,
  `id_mailchimp_file` text DEFAULT NULL,
  `date_created_file` date DEFAULT NULL,
  `date_updated_file` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_file`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `files`
--

LOCK TABLES `files` WRITE;
/*!40000 ALTER TABLE `files` DISABLE KEYS */;
INSERT INTO `files` VALUES
(2,1,'Vinagre (11)','png','image/png',405934,'https://pos.desarrolloweb24siete.com//views/assets/files/690253664dc6922.png',NULL,NULL,'2025-10-29','2025-10-29 17:48:22'),
(3,1,'Vinagre (10)','png','image/png',416491,'https://pos.desarrolloweb24siete.com//views/assets/files/690253669277c22.png',NULL,NULL,'2025-10-29','2025-10-29 17:48:22'),
(5,1,'Vinagre (2)','png','image/png',450078,'https://pos.desarrolloweb24siete.com//views/assets/files/69025366c5b8622.png',NULL,NULL,'2025-10-29','2025-10-29 17:48:22'),
(6,1,'ChatGPT Image 22 oct 2025, 12_24_24 p.m.','png','image/png',1472808,'https://pos.desarrolloweb24siete.com//views/assets/files/69025436db56e50.png',NULL,NULL,'2025-10-29','2025-10-29 17:51:50'),
(7,1,'VitalHerbs','png','image/png',564976,'https://pos.desarrolloweb24siete.com/views/assets/files/69027c36d94d230.png',NULL,NULL,'2025-10-29','2025-10-29 20:42:30'),
(8,1,'Vinagre (12)','png','image/png',434964,'https://pos.desarrolloweb24siete.com/views/assets/files/690280fb326e451.png',NULL,NULL,'2025-10-29','2025-10-29 21:02:51'),
(9,1,'Vinagre (6)','png','image/png',417891,'https://pos.desarrolloweb24siete.com/views/assets/files/690280fb3530c51.png',NULL,NULL,'2025-10-29','2025-10-29 21:02:51'),
(10,1,'Vinagre (5)','png','image/png',416664,'https://pos.desarrolloweb24siete.com/views/assets/files/690280fb5ae1851.png',NULL,NULL,'2025-10-29','2025-10-29 21:02:51'),
(11,1,'Vinagre (9)','png','image/png',434308,'https://pos.desarrolloweb24siete.com/views/assets/files/690280fb8968251.png',NULL,NULL,'2025-10-29','2025-10-29 21:02:51'),
(12,1,'Golden Milk','png','image/png',554308,'https://pos.desarrolloweb24siete.com/views/assets/files/69053796c997b30.png',NULL,NULL,'2025-10-31','2025-11-07 21:23:37'),
(13,1,'Viangre de Mora','jpeg','image/jpeg',53055,'https://pos.desarrolloweb24siete.com/views/assets/files/690ecc1c8438a36.jpeg',NULL,NULL,'2025-11-08','2025-11-08 04:55:55');
/*!40000 ALTER TABLE `files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `folders`
--

DROP TABLE IF EXISTS `folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `folders` (
  `id_folder` int(11) NOT NULL AUTO_INCREMENT,
  `name_folder` text DEFAULT NULL,
  `size_folder` text DEFAULT NULL,
  `total_folder` double DEFAULT 0,
  `max_upload_folder` text DEFAULT NULL,
  `url_folder` text DEFAULT NULL,
  `keys_folder` text DEFAULT NULL,
  `date_created_folder` date DEFAULT NULL,
  `date_updated_folder` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_folder`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `folders`
--

LOCK TABLES `folders` WRITE;
/*!40000 ALTER TABLE `folders` DISABLE KEYS */;
INSERT INTO `folders` VALUES
(1,'Server','200000000000',5621477,'500000000','https://pos.desarrolloweb24siete.com',NULL,'2024-12-16','2025-11-08 04:50:36');
/*!40000 ALTER TABLE `folders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `incomes`
--

DROP TABLE IF EXISTS `incomes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `incomes` (
  `id_income` int(11) NOT NULL AUTO_INCREMENT,
  `concept_income` text DEFAULT NULL,
  `amount_income` double DEFAULT 0,
  `date_income` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_cash_income` int(11) DEFAULT 0,
  `id_admin_income` int(11) DEFAULT 0,
  `id_office_income` int(11) DEFAULT 0,
  `date_created_income` date DEFAULT NULL,
  `date_updated_income` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_income`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `incomes`
--

LOCK TABLES `incomes` WRITE;
/*!40000 ALTER TABLE `incomes` DISABLE KEYS */;
/*!40000 ALTER TABLE `incomes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indirect_cost_types`
--

DROP TABLE IF EXISTS `indirect_cost_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `indirect_cost_types` (
  `id_indirect_type` int(11) NOT NULL AUTO_INCREMENT,
  `name_indirect_type` text NOT NULL,
  `description_indirect_type` text DEFAULT NULL,
  `id_office_indirect_type` int(11) NOT NULL,
  `status_indirect_type` int(11) DEFAULT 1,
  `date_created_indirect_type` date DEFAULT NULL,
  `date_updated_indirect_type` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_indirect_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indirect_cost_types`
--

LOCK TABLES `indirect_cost_types` WRITE;
/*!40000 ALTER TABLE `indirect_cost_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `indirect_cost_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_requests`
--

DROP TABLE IF EXISTS `inventory_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_requests` (
  `id_request` int(11) NOT NULL AUTO_INCREMENT,
  `id_admin_request` int(11) NOT NULL,
  `id_office_request` int(11) NOT NULL,
  `id_product_request` int(11) NOT NULL,
  `qty_request` double NOT NULL,
  `status_request` text DEFAULT 'pendiente',
  `id_dispatched_by_request` int(11) DEFAULT NULL,
  `qty_dispatched_request` double DEFAULT NULL,
  `notes_request` text DEFAULT NULL,
  `notes_dispatcher_request` text DEFAULT NULL,
  `id_warehouse_request` int(11) DEFAULT NULL,
  `date_created_request` timestamp NULL DEFAULT current_timestamp(),
  `date_updated_request` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_request`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_requests`
--

LOCK TABLES `inventory_requests` WRITE;
/*!40000 ALTER TABLE `inventory_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id_invoice` int(11) NOT NULL AUTO_INCREMENT,
  `id_order_invoice` int(11) NOT NULL,
  `nit_invoice` varchar(50) DEFAULT NULL,
  `subtotal_invoice` double NOT NULL DEFAULT 0,
  `total_invoice` double NOT NULL DEFAULT 0,
  `status_invoice` varchar(50) DEFAULT 'pendiente',
  `id_office_invoice` int(11) NOT NULL,
  `date_created_invoice` date DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `client_name_invoice` varchar(255) DEFAULT NULL,
  `date_updated_invoice` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_invoice`),
  KEY `idx_invoices_order` (`id_order_invoice`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_supplies`
--

DROP TABLE IF EXISTS `lab_supplies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_supplies` (
  `id_supply` int(11) NOT NULL AUTO_INCREMENT,
  `name_supply` text NOT NULL,
  `unit_supply` varchar(20) NOT NULL,
  `stock_supply` double DEFAULT 0,
  `price_supply` double DEFAULT 0,
  `id_office_supply` int(11) NOT NULL,
  `id_supplier_supply` int(11) DEFAULT NULL,
  `status_supply` int(11) DEFAULT 1,
  `date_created_supply` date DEFAULT NULL,
  `date_updated_supply` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_supply`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_supplies`
--

LOCK TABLES `lab_supplies` WRITE;
/*!40000 ALTER TABLE `lab_supplies` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_supplies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modules`
--

DROP TABLE IF EXISTS `modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `modules` (
  `id_module` int(11) NOT NULL AUTO_INCREMENT,
  `id_page_module` int(11) DEFAULT 0,
  `type_module` text DEFAULT NULL,
  `title_module` text DEFAULT NULL,
  `suffix_module` text DEFAULT NULL,
  `content_module` text DEFAULT NULL,
  `width_module` int(11) DEFAULT 100,
  `editable_module` int(11) DEFAULT 1,
  `date_created_module` date DEFAULT NULL,
  `date_updated_module` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_module`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modules`
--

LOCK TABLES `modules` WRITE;
/*!40000 ALTER TABLE `modules` DISABLE KEYS */;
INSERT INTO `modules` VALUES
(1,2,'breadcrumbs','Administradores',NULL,NULL,100,1,'2024-12-16','2024-12-16 21:46:23'),
(2,2,'tables','admins','admin','',100,0,'2024-12-16','2024-12-19 20:12:22'),
(3,4,'breadcrumbs','sucursales','','',100,1,'2024-12-17','2024-12-16 23:10:34'),
(4,4,'tables','offices','office','',100,1,'2024-12-17','2024-12-16 23:17:23'),
(5,5,'breadcrumbs','clientes','','',100,1,'2024-12-18','2024-12-18 19:33:43'),
(6,5,'tables','clients','client','',100,1,'2024-12-18','2024-12-18 19:37:39'),
(7,6,'breadcrumbs','categorías','','',100,1,'2024-12-18','2024-12-18 20:12:25'),
(8,6,'tables','categories','category','',100,1,'2024-12-18','2024-12-18 20:14:59'),
(9,7,'breadcrumbs','productos','','',100,1,'2024-12-18','2024-12-18 20:33:10'),
(10,7,'tables','products','product','',100,1,'2024-12-18','2024-12-18 20:38:30'),
(11,8,'breadcrumbs','compras','','',100,1,'2024-12-18','2024-12-18 21:37:39'),
(13,9,'breadcrumbs','Órdenes','','',100,1,'2024-12-18','2024-12-18 22:35:32'),
(14,9,'tables','orders','order','',100,0,'2024-12-18','2024-12-18 22:45:34'),
(15,10,'breadcrumbs','ventas','','',100,1,'2024-12-18','2024-12-18 22:50:59'),
(16,10,'tables','sales','sale','',100,0,'2024-12-18','2024-12-18 22:54:24'),
(17,11,'breadcrumbs','caja','','',100,1,'2024-12-19','2024-12-18 23:02:12'),
(18,11,'tables','cashs','cash','',100,1,'2024-12-19','2024-12-18 23:09:25'),
(19,12,'breadcrumbs','gastos','','',100,1,'2024-12-19','2024-12-18 23:12:39'),
(20,12,'tables','bills','bill','',100,1,'2024-12-19','2024-12-18 23:14:38'),
(21,1,'custom','orders','','',100,1,'2024-12-20','2024-12-20 16:00:40'),
(22,1,'custom','products','','',50,1,'2024-12-20','2024-12-20 16:02:03'),
(23,1,'custom','panel','','',50,1,'2024-12-20','2024-12-20 16:02:18'),
(39,15,'breadcrumbs','proveedores','','',100,1,'2025-10-29','2025-10-29 19:59:36'),
(40,15,'tables','suppliers','supplier','',100,1,'2025-10-29','2025-10-29 20:02:41'),
(41,8,'tables','purchases','purchase','',100,1,'2025-10-31','2025-10-31 23:07:11'),
(42,0,'tables','incomes','income',NULL,100,1,'2026-06-05','2026-06-05 18:18:07'),
(43,0,'tables','incomes','income',NULL,100,1,'2026-06-05','2026-06-05 18:18:07'),
(44,17,'tables','warehouses','warehouse','Almacenes',100,1,'2026-06-11','2026-06-11 13:39:55');
/*!40000 ALTER TABLE `modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `offices`
--

DROP TABLE IF EXISTS `offices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `offices` (
  `id_office` int(11) NOT NULL AUTO_INCREMENT,
  `title_office` text DEFAULT NULL,
  `address_office` text DEFAULT NULL,
  `phone_office` text DEFAULT NULL,
  `dni_office` text DEFAULT NULL,
  `date_created_office` date DEFAULT NULL,
  `date_updated_office` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `type_office` text DEFAULT 'sucursal',
  PRIMARY KEY (`id_office`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offices`
--

LOCK TABLES `offices` WRITE;
/*!40000 ALTER TABLE `offices` DISABLE KEYS */;
INSERT INTO `offices` VALUES
(3,'Sucursal JE','Calle sucre Esquina Cobija','60836039','42135423524-3','2024-12-17','2025-10-31 02:30:57','sucursal'),
(4,'Sucursal Montero','Calle montero N° 24','7900900','42135423524-3','2025-10-30','2025-10-31 19:09:11','sucursal'),
(5,'Sucursal SE','av banzer','12345678','112321',NULL,'2026-06-10 02:00:08','sucursal'),
(6,'sadadsa','daas','111111111',NULL,'2026-06-11','2026-06-11 14:08:53','almacen'),
(7,'Av beni','beni 4to-5to anillo','12345678','4142352',NULL,'2026-06-11 14:45:07','sucursal'),
(8,'Almacen remanaso','remanso, octavo anillo','12345678',NULL,'2026-06-11','2026-06-11 15:09:48','almacen');
/*!40000 ALTER TABLE `offices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_expenses`
--

DROP TABLE IF EXISTS `order_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_expenses` (
  `id_expense` int(11) NOT NULL AUTO_INCREMENT,
  `id_order_expense` int(11) NOT NULL,
  `concept_expense` varchar(255) DEFAULT NULL,
  `amount_expense` double DEFAULT 0,
  `id_admin_expense` int(11) DEFAULT 0,
  `date_created_expense` date DEFAULT NULL,
  PRIMARY KEY (`id_expense`),
  KEY `idx_order_expense` (`id_order_expense`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_expenses`
--

LOCK TABLES `order_expenses` WRITE;
/*!40000 ALTER TABLE `order_expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id_order` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_order` text DEFAULT NULL,
  `id_admin_order` int(11) DEFAULT 0,
  `id_client_order` int(11) DEFAULT 0,
  `subtotal_order` double DEFAULT 0,
  `discount_order` double DEFAULT 0,
  `tax_order` double DEFAULT 0,
  `total_order` double DEFAULT 0,
  `method_order` text DEFAULT NULL,
  `transfer_order` text DEFAULT NULL,
  `status_order` text DEFAULT NULL,
  `date_order` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_office_order` int(11) DEFAULT 0,
  `date_created_order` date DEFAULT NULL,
  `date_updated_order` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `qr_ref_order` varchar(255) DEFAULT NULL,
  `method_detail_order` text DEFAULT NULL,
  `invoice_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id_order`)
) ENGINE=InnoDB AUTO_INCREMENT=124 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES
(37,'997359461178',1,16,450,0,0,450,'efectivo','','Completada','2025-10-31 23:51:28',3,'2025-10-31','2025-10-31 23:51:28',NULL,NULL,0),
(39,'764951191835',1,14,180,0,0,180,'efectivo','','Completada','2025-11-01 00:16:00',3,'2025-10-31','2025-11-01 00:16:00',NULL,NULL,0),
(51,'294565881772',1,16,100,0,0,100,'efectivo','','Completada','2025-11-01 01:30:00',3,'2025-10-31','2025-11-01 01:30:00',NULL,NULL,0),
(53,'135424887762',1,16,480,14,0,466,'tarjeta','','Completada','2025-11-01 01:55:29',3,'2025-10-31','2025-11-01 01:55:29',NULL,NULL,0),
(54,'451826576589',14,15,260,5,0,255,'transferencia','13154','Completada','2025-11-01 01:56:36',3,'2025-10-31','2025-11-01 01:56:36',NULL,NULL,0),
(55,'412382728395',14,14,420,10,0,410,'efectivo','','Completada','2025-11-01 01:57:36',3,'2025-10-31','2025-11-01 01:57:36',NULL,NULL,0),
(56,'384391765821',14,14,160,5,0,155,'efectivo','','Completada','2025-11-01 02:01:29',3,'2025-10-31','2025-11-01 02:01:29',NULL,NULL,0),
(57,'586924181416',1,16,570,14,0,556,'efectivo','','Completada','2025-11-04 03:02:22',3,'2025-11-03','2025-11-04 03:02:22',NULL,NULL,0),
(59,'241997685326',1,14,160,5,0,155,'efectivo','','Completada','2025-11-04 03:26:42',3,'2025-11-03','2025-11-04 03:26:42',NULL,NULL,0),
(60,'942985627493',1,15,160,5,0,155,'efectivo','','Completada','2025-11-04 04:02:14',3,'2025-11-04','2025-11-04 04:02:14',NULL,NULL,0),
(61,'945926878319',1,16,160,5,0,155,'efectivo','','Completada','2025-11-04 04:10:08',3,'2025-11-04','2025-11-04 04:10:08',NULL,NULL,0),
(62,'319892573781',1,15,270,0,0,270,'efectivo','','Completada','2025-11-04 04:11:54',3,'2025-11-04','2025-11-04 04:11:54',NULL,NULL,0),
(63,'249371821796',1,14,1000,19,0,981,'tarjeta','','Completada','2025-11-04 04:19:40',4,'2025-11-04','2025-11-04 04:19:40',NULL,NULL,0),
(64,'489615853148',14,14,450,0,0,450,'transferencia','51515','Completada','2025-11-04 18:45:25',3,'2025-11-04','2025-11-04 18:45:25',NULL,NULL,0),
(65,'545742139859',14,16,540,0,0,540,'efectivo','','Completada','2025-11-04 18:47:45',3,'2025-11-04','2025-11-04 18:47:45',NULL,NULL,0),
(66,'869571447259',1,14,270,8,0,262,'efectivo','','Completada','2025-11-07 20:39:22',3,'2025-11-07','2025-11-07 20:39:22',NULL,NULL,0),
(67,'442255752137',14,15,340,8,0,332,'efectivo','','Completada','2025-11-07 21:27:31',3,'2025-11-07','2025-11-07 21:27:31',NULL,NULL,0),
(68,'668178146271',14,14,90,0,0,90,'efectivo','','Completada','2025-11-07 21:27:50',3,'2025-11-07','2025-11-07 21:27:50',NULL,NULL,0),
(69,'564898521193',14,14,90,3,0,87,'efectivo','','Completada','2025-11-07 21:39:03',3,'2025-11-07','2025-11-07 21:39:03',NULL,NULL,0),
(70,'585964977638',14,14,90,3,0,87,'efectivo','','Completada','2025-11-07 21:39:59',3,'2025-11-07','2025-11-07 21:39:59',NULL,NULL,0),
(71,'267634721578',14,14,90,3,0,87,'tarjeta','','Completada','2025-11-07 21:40:47',3,'2025-11-07','2025-11-07 21:40:47',NULL,NULL,0),
(72,'139164815844',14,15,90,3,0,87,'efectivo','','Completada','2025-11-07 21:45:02',3,'2025-11-07','2025-11-07 21:45:02',NULL,NULL,0),
(73,'259183795615',14,16,90,3,0,87,'efectivo','','Completada','2025-11-07 21:48:07',3,'2025-11-07','2025-11-07 21:48:07',NULL,NULL,0),
(74,'695389841791',14,14,90,3,0,87,'efectivo','','Completada','2025-11-07 21:54:13',3,'2025-11-07','2025-11-07 21:54:13',NULL,NULL,0),
(75,'432179945872',14,15,160,5,0,155,'tarjeta','','Completada','2025-11-07 21:58:28',3,'2025-11-07','2025-11-07 21:58:28',NULL,NULL,0),
(76,'989346572592',14,14,160,5,0,155,'tarjeta','','Completada','2025-11-07 21:59:34',3,'2025-11-07','2025-11-07 21:59:34',NULL,NULL,0),
(77,'996586287497',14,15,90,0,0,90,'efectivo','','Completada','2025-11-07 22:05:46',3,'2025-11-07','2025-11-07 22:05:46',NULL,NULL,0),
(78,'683149716497',14,14,90,3,0,87,'efectivo','','Completada','2025-11-07 22:12:08',3,'2025-11-07','2025-11-07 22:12:08',NULL,NULL,0),
(79,'249922468557',14,15,160,5,0,155,'tarjeta','','Completada','2025-11-07 22:16:55',3,'2025-11-07','2025-11-07 22:16:55',NULL,NULL,0),
(80,'358186753427',14,14,160,5,0,155,'tarjeta','','Completada','2025-11-07 22:17:12',3,'2025-11-07','2025-11-07 22:17:12',NULL,NULL,0),
(81,'187833718612',14,15,160,5,0,155,'tarjeta','','Completada','2025-11-07 22:18:52',3,'2025-11-07','2025-11-07 22:18:52',NULL,NULL,0),
(82,'238594925138',14,16,90,0,0,90,'efectivo','','Completada','2025-11-07 22:30:50',3,'2025-11-07','2025-11-07 22:30:50',NULL,NULL,0),
(83,'453212231776',14,15,90,0,0,90,'tarjeta','','Completada','2025-11-07 22:31:19',3,'2025-11-07','2025-11-07 22:31:19',NULL,NULL,0),
(84,'554792314211',14,0,0,0,0,0,NULL,NULL,'Pendiente','2025-11-07 22:31:26',3,'2025-11-07','2025-11-07 22:31:26',NULL,NULL,0),
(85,'143452613189',14,14,160,5,0,155,'tarjeta','','Completada','2025-11-07 22:31:50',3,'2025-11-07','2025-11-07 22:31:50',NULL,NULL,0),
(86,'826536947531',14,14,250,5,0,245,'efectivo','','Completada','2025-11-08 04:39:55',3,'2025-11-08','2025-11-08 04:39:55',NULL,NULL,0),
(87,'142479538853',14,15,95,0,0,95,'efectivo','','Completada','2025-11-08 04:57:00',3,'2025-11-08','2025-11-08 04:57:00',NULL,NULL,0),
(89,'177385634896',14,16,90,0,0,90,'tarjeta','','Completada','2025-11-08 04:57:47',3,'2025-11-08','2025-11-08 04:57:47',NULL,NULL,0),
(93,'564915679418',15,0,0,0,0,0,NULL,NULL,'Pendiente','2026-06-05 18:43:16',3,'2026-06-05','2026-06-05 18:43:16',NULL,NULL,0),
(94,'683172173432',15,0,0,0,0,0,NULL,NULL,'Pendiente','2026-06-05 18:47:15',3,'2026-06-05','2026-06-05 18:47:15',NULL,NULL,0),
(95,'897425267214',15,0,0,0,0,0,NULL,NULL,'Pendiente','2026-06-05 18:56:57',3,'2026-06-05','2026-06-05 18:56:57',NULL,NULL,0),
(96,'694927742198',1,0,0,0,0,0,NULL,NULL,'Pendiente','2026-06-05 19:00:42',3,'2026-06-05','2026-06-05 19:00:42',NULL,NULL,0),
(97,'143973198425',15,0,0,0,0,0,NULL,NULL,'Pendiente','2026-06-05 19:14:27',3,'2026-06-05','2026-06-05 19:14:27',NULL,NULL,0),
(98,'869825626384',15,0,0,0,0,0,NULL,NULL,'Pendiente','2026-06-05 19:24:05',3,'2026-06-05','2026-06-05 19:24:05',NULL,NULL,0),
(99,'234577278854',15,18,0,0,0,0,NULL,NULL,'Pendiente','2026-06-05 19:37:15',3,'2026-06-05','2026-06-05 19:37:15',NULL,NULL,0),
(102,'817185852924',15,18,0,0,0,0,NULL,NULL,'Pendiente','2026-06-08 20:56:14',3,'2026-06-08','2026-06-08 20:56:14',NULL,NULL,0),
(103,'589912738571',15,18,0,0,0,0,'transferencia','1','Completada','2026-06-09 13:50:11',3,'2026-06-09','2026-06-09 17:50:11','',NULL,0),
(104,'567532626971',14,14,0,0,0,0,'efectivo','','Completada','2026-06-09 13:56:38',3,'2026-06-09','2026-06-09 17:56:38','',NULL,0),
(105,'814925386349',15,14,0,0,0,0,'efectivo','','Completada','2026-06-09 21:59:40',3,'2026-06-09','2026-06-10 01:59:40','',NULL,0),
(106,'qwewqeqwe',1,15,0,0,0,0,'efectivo','qwe','Completada','2026-06-10 02:03:24',5,NULL,'2026-06-10 02:03:24',NULL,NULL,0),
(107,'495758665189',15,18,0,0,0,0,'efectivo','','Completada','2026-06-11 10:41:16',3,'2026-06-11','2026-06-11 14:41:16','',NULL,0),
(108,'884972733596',14,16,0,0,0,0,'efectivo','','Completada','2026-06-11 10:42:42',3,'2026-06-11','2026-06-11 14:42:42','',NULL,0),
(109,'279416831541',14,18,0,0,0,0,'efectivo','','Completada','2026-06-11 10:43:03',3,'2026-06-11','2026-06-11 14:43:03','',NULL,0),
(110,'321794947562',20,0,0,0,0,0,NULL,NULL,'Pendiente','2026-06-11 15:12:16',7,'2026-06-11','2026-06-11 15:12:16',NULL,NULL,0),
(111,'864527551687',20,18,0,0,0,0,NULL,NULL,'Pendiente','2026-06-11 15:13:21',7,'2026-06-11','2026-06-11 15:13:21',NULL,NULL,0),
(112,'535481967423',20,15,0,0,0,0,'efectivo','','Completada','2026-06-11 11:13:51',7,'2026-06-11','2026-06-11 15:13:51','',NULL,0),
(113,'497683167485',20,18,0,0,0,0,'efectivo','','Completada','2026-06-11 11:27:51',7,'2026-06-11','2026-06-11 15:27:51','',NULL,0),
(114,'821994257672',20,18,45,0,0,45,'efectivo','','Completada','2026-06-11 11:47:22',7,'2026-06-11','2026-06-11 15:47:22','',NULL,0),
(115,'271441456337',20,15,45,0,0,45,'efectivo','','Completada','2026-06-11 11:48:25',7,'2026-06-11','2026-06-11 15:48:25','',NULL,0),
(116,'662127929388',20,15,45,0,0,45,'efectivo','','Completada','2026-06-11 12:05:39',7,'2026-06-11','2026-06-11 16:05:39','',NULL,0),
(117,'378176438571',15,14,45,1,0,44,'transferencia','12313','Completada','2026-06-11 13:34:19',3,'2026-06-11','2026-06-11 17:34:19','',NULL,0),
(118,'411951863325',14,14,80,2,0,78,'QR','1','Completada','2026-06-11 13:36:47',3,'2026-06-11','2026-06-11 17:36:47','',NULL,0),
(119,'596985516772',15,14,80,2,0,78,NULL,NULL,'Pendiente','2026-06-11 17:55:22',3,'2026-06-11','2026-06-11 17:55:22',NULL,NULL,0),
(120,'142925268563',15,18,45,0,0,45,NULL,NULL,'Pendiente','2026-06-11 17:58:16',3,'2026-06-11','2026-06-11 17:58:16',NULL,NULL,0),
(121,'981496436238',15,18,45,0,0,45,NULL,NULL,'Pendiente','2026-06-11 17:58:31',3,'2026-06-11','2026-06-11 17:58:31',NULL,NULL,0),
(122,'269322391471',15,18,45,1,0,44,'QR','Q','Completada','2026-06-11 14:02:03',3,'2026-06-11','2026-06-11 18:02:03','',NULL,0),
(123,'658937217248',15,18,45,1,0,44,NULL,NULL,'Pendiente','2026-06-11 18:10:44',3,'2026-06-11','2026-06-11 18:10:44',NULL,NULL,0);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `packaging_catalog`
--

DROP TABLE IF EXISTS `packaging_catalog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `packaging_catalog` (
  `id_packaging` int(11) NOT NULL AUTO_INCREMENT,
  `name_packaging` varchar(255) NOT NULL,
  `price_packaging` double DEFAULT 0,
  `unit_packaging` varchar(50) DEFAULT 'unidades',
  `status_packaging` int(11) DEFAULT 1,
  `date_created_packaging` date DEFAULT NULL,
  `date_updated_packaging` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_packaging`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `packaging_catalog`
--

LOCK TABLES `packaging_catalog` WRITE;
/*!40000 ALTER TABLE `packaging_catalog` DISABLE KEYS */;
/*!40000 ALTER TABLE `packaging_catalog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id_page` int(11) NOT NULL AUTO_INCREMENT,
  `title_page` text DEFAULT NULL,
  `url_page` text DEFAULT NULL,
  `icon_page` text DEFAULT NULL,
  `type_page` text DEFAULT NULL,
  `order_page` int(11) DEFAULT 1,
  `date_created_page` date DEFAULT NULL,
  `date_updated_page` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_page`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES
(1,'POS','pos','bi bi-house-door-fill','modules',1,'2024-12-16','2025-10-31 20:18:40'),
(2,'Admins','admins','bi bi-person-fill-gear','modules',4,'2024-12-16','2025-10-31 20:18:40'),
(3,'Archivos','archivos','bi bi-file-earmark-image','custom',15,'2024-12-16','2025-11-01 00:34:29'),
(4,'Sucursales','sucursales','bi bi-shop','modules',3,'2024-12-17','2025-10-31 20:18:40'),
(5,'Clientes','clientes','bi bi-people','modules',5,'2024-12-18','2025-10-31 20:18:46'),
(6,'Categorías','categorias','bi bi-card-list','modules',6,'2024-12-18','2025-10-31 20:18:46'),
(7,'Productos','productos','bi bi-box','modules',8,'2024-12-18','2025-10-31 20:18:46'),
(8,'Compras','compras','bi bi-basket-fill','modules',9,'2024-12-18','2025-10-31 20:18:46'),
(9,'Órdenes','ordenes','bi bi-ticket-detailed','modules',10,'2024-12-18','2025-10-31 20:18:46'),
(10,'Ventas','ventas','bi bi-cash-coin','modules',11,'2024-12-18','2025-10-31 20:18:46'),
(11,'Caja','caja','fas fa-cash-register','modules',2,'2024-12-19','2025-10-31 20:18:40'),
(12,'Gastos','gastos','fas fa-money-bill-wave','modules',12,'2024-12-19','2025-10-31 20:18:46'),
(15,'Proveedores','proveedores','bi bi-person-bounding-box','modules',7,'2025-10-29','2025-10-31 20:18:46'),
(16,'Reportes','reports','bi bi-file-earmark-excel-fill','custom',14,'2025-10-31','2025-11-01 00:34:29'),
(17,'Almacenes','almacenes','fas fa-warehouse','modules',26,'2026-06-11','2026-06-11 13:39:55');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_inventory`
--

DROP TABLE IF EXISTS `product_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_inventory` (
  `id_inventory` int(11) NOT NULL AUTO_INCREMENT,
  `id_product_inventory` int(11) NOT NULL,
  `id_office_inventory` int(11) NOT NULL,
  `stock_inventory` double DEFAULT 0,
  `status_inventory` int(11) DEFAULT 1,
  `date_created_inventory` date DEFAULT NULL,
  `date_updated_inventory` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_inventory`),
  UNIQUE KEY `uq_product_office` (`id_product_inventory`,`id_office_inventory`),
  KEY `idx_prodinv_prod_off` (`id_product_inventory`,`id_office_inventory`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_inventory`
--

LOCK TABLES `product_inventory` WRITE;
/*!40000 ALTER TABLE `product_inventory` DISABLE KEYS */;
INSERT INTO `product_inventory` VALUES
(1,12,3,0,0,'2025-10-31','2026-06-05 00:18:10'),
(2,13,4,16,1,'2025-10-31','2026-06-05 00:18:10'),
(3,14,3,6,1,'2025-10-31','2026-06-11 17:36:47'),
(4,15,4,21,1,'2025-10-31','2026-06-05 00:18:10'),
(5,16,3,11,1,'2025-11-04','2026-06-05 00:18:10'),
(6,17,4,0,1,'2025-11-04','2026-06-05 00:18:10'),
(7,18,3,3,1,'2025-11-04','2026-06-11 18:02:03'),
(8,19,4,0,1,'2025-11-04','2026-06-05 00:18:10'),
(9,21,3,3,1,'2025-11-04','2026-06-05 00:18:10'),
(10,22,4,0,1,'2025-11-04','2026-06-05 00:18:10'),
(11,23,3,2,1,'2025-11-04','2026-06-05 00:18:10'),
(12,24,4,0,1,'2025-11-04','2026-06-05 00:18:10'),
(13,25,3,9,1,'2025-11-08','2026-06-05 00:18:10'),
(14,26,4,0,1,'2025-11-08','2026-06-05 00:18:10'),
(18,27,6,0,1,'2026-06-08','2026-06-08 21:58:06'),
(19,28,6,100,1,'2026-06-08','2026-06-08 21:58:55'),
(20,14,4,12,1,'2026-06-10','2026-06-10 02:03:09'),
(21,16,8,0,1,'2026-06-11','2026-06-11 15:27:34'),
(22,16,7,0,1,'2026-06-11','2026-06-11 16:05:39'),
(24,23,8,0,1,'2026-06-11','2026-06-11 16:10:26'),
(25,23,7,7,1,'2026-06-11','2026-06-11 16:10:26');
/*!40000 ALTER TABLE `product_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_material_costs`
--

DROP TABLE IF EXISTS `production_material_costs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `production_material_costs` (
  `id_prod_material_cost` int(11) NOT NULL AUTO_INCREMENT,
  `id_production_mat_cost` int(11) NOT NULL,
  `id_raw_material_mat_cost` int(11) NOT NULL,
  `id_entry_used_mat_cost` int(11) NOT NULL,
  `qty_used_mat_cost` double NOT NULL,
  `unit_price_at_production` double NOT NULL,
  `total_cost_mat_cost` double NOT NULL,
  PRIMARY KEY (`id_prod_material_cost`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_material_costs`
--

LOCK TABLES `production_material_costs` WRITE;
/*!40000 ALTER TABLE `production_material_costs` DISABLE KEYS */;
INSERT INTO `production_material_costs` VALUES
(1,1,1,1,50,10,500),
(2,1,1,1,50,10,500);
/*!40000 ALTER TABLE `production_material_costs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productions`
--

DROP TABLE IF EXISTS `productions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `productions` (
  `id_production` int(11) NOT NULL AUTO_INCREMENT,
  `id_recipe_production` int(11) NOT NULL,
  `id_product_production` int(11) NOT NULL,
  `batches_production` double NOT NULL,
  `total_qty_production` double NOT NULL,
  `proj_materials_cost` double DEFAULT 0,
  `proj_labor_cost` double DEFAULT 0,
  `proj_indirect_cost` double DEFAULT 0,
  `proj_total_cost` double DEFAULT 0,
  `proj_unit_cost` double DEFAULT 0,
  `real_materials_cost` double DEFAULT 0,
  `real_labor_cost` double DEFAULT 0,
  `real_indirect_cost` double DEFAULT 0,
  `real_total_cost` double DEFAULT 0,
  `real_unit_cost` double DEFAULT 0,
  `status_production` text DEFAULT 'pendiente',
  `start_date_production` date DEFAULT NULL,
  `end_date_production` date DEFAULT NULL,
  `notes_production` text DEFAULT NULL,
  `id_admin_production` int(11) NOT NULL,
  `id_office_production` int(11) NOT NULL,
  `date_created_production` date DEFAULT NULL,
  `date_updated_production` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_packaged_product` int(11) DEFAULT 0,
  `pkg_labor_cost` double DEFAULT 0,
  `pkg_indirect_cost` double DEFAULT 0,
  `real_bulk_qty` double DEFAULT NULL,
  `yield_variance` double DEFAULT NULL,
  `yield_variance_pct` double DEFAULT NULL,
  `qty_packaged_production` double DEFAULT NULL,
  `qty_approved_production` double DEFAULT NULL,
  `qty_rejected_production` double DEFAULT NULL,
  `result_qc_production` varchar(30) DEFAULT NULL,
  `notes_qc_production` text DEFAULT NULL,
  `waste_qty_production` double DEFAULT 0,
  `waste_packaged_qty` double DEFAULT 0,
  `waste_loss_qty` double DEFAULT 0,
  `pkg_name_production` text DEFAULT NULL,
  PRIMARY KEY (`id_production`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productions`
--

LOCK TABLES `productions` WRITE;
/*!40000 ALTER TABLE `productions` DISABLE KEYS */;
INSERT INTO `productions` VALUES
(1,1,27,0.5,50,500,5,50,555,11.1,500,5,50,555,5.55,'completado','2026-06-08',NULL,NULL,19,6,'2026-06-08','2026-06-08 21:58:55',28,0,0,NULL,NULL,NULL,100,100,0,'aprobado','',0,0,0,NULL);
/*!40000 ALTER TABLE `productions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id_product` int(11) NOT NULL AUTO_INCREMENT,
  `title_product` text DEFAULT NULL,
  `img_product` text DEFAULT NULL,
  `id_category_product` int(11) DEFAULT 0,
  `sku_product` text DEFAULT NULL,
  `unit_product` text DEFAULT NULL,
  `rte_product` text DEFAULT NULL,
  `stock_product` text DEFAULT NULL,
  `discount_product` double DEFAULT 0,
  `status_product` int(11) DEFAULT 1,
  `id_office_product` int(11) DEFAULT 0,
  `code_product` text DEFAULT NULL,
  `date_created_product` date DEFAULT NULL,
  `date_updated_product` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_compound_product` int(11) DEFAULT 0,
  `origin_office_product` int(11) DEFAULT 0,
  `initial_stock_product` double DEFAULT 0,
  `combo_price_mode` varchar(20) DEFAULT 'descuento',
  PRIMARY KEY (`id_product`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES
(12,'Vinagre+de+Fresa','https%3A%2F%2Fpos.desarrolloweb24siete.com%2F%2Fviews%2Fassets%2Ffiles%2F69025366c5b8622.png',8,'VF1','1Lt',NULL,'0',0,0,0,'133331441','2025-10-31','2026-06-08 03:13:40',0,0,0,'descuento'),
(14,'Green+Powder','https%3A%2F%2Fpos.desarrolloweb24siete.com%2Fviews%2Fassets%2Ffiles%2F69027c36d94d230.png',10,'VHGP10','500gr',NULL,'18',3,1,0,'33322212215','2025-10-31','2026-06-11 17:36:47',0,0,0,'descuento'),
(16,'Vinagre+de+Pitaya','https%3A%2F%2Fpos.desarrolloweb24siete.com%2Fviews%2Fassets%2Ffiles%2F690280fb8968251.png',8,'VP10','1Lt',NULL,'11',0,1,0,'14445455','2025-11-04','2026-06-11 16:05:39',0,0,0,'descuento'),
(18,'Vinagre de Durazno','https://pos.desarrolloweb24siete.com/views/assets/files/690280fb5ae1851.png',8,'VD10','1Lt',NULL,'3',3,1,0,'6061611165','2025-11-04','2026-06-11 18:02:03',0,0,0,'descuento'),
(21,'Vinagre de Perejil','https://pos.desarrolloweb24siete.com/views/assets/files/690280fb326e451.png',8,'dsf','1Lt',NULL,'3',3,1,0,'61511515','2025-11-04','2026-06-08 03:13:40',0,0,0,'descuento'),
(23,'Vinagre+de+Mel%C3%B3n','https%3A%2F%2Fpos.desarrolloweb24siete.com%2Fviews%2Fassets%2Ffiles%2F690280fb3530c51.png',8,'VM10','1Lt',NULL,'9',0,1,0,'151515511','2025-11-04','2026-06-11 16:08:54',0,0,0,'descuento'),
(27,'Vinagre de Frutilla',NULL,0,NULL,'L','0','0',0,1,0,NULL,NULL,'2026-06-08 21:58:06',1,0,0,'descuento'),
(28,'Vinagre de Frutilla 500ml',NULL,0,NULL,'und','5.55','100',0,1,6,NULL,NULL,'2026-06-08 21:58:55',1,0,0,'descuento'),
(29,'dasdsadadsss','',9,'dass','1Lt',NULL,'12',0,1,3,'123',NULL,'2026-06-10 02:02:49',0,0,0,'descuento');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchases`
--

DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchases` (
  `id_purchase` int(11) NOT NULL AUTO_INCREMENT,
  `id_supplier_purchase` int(11) DEFAULT 0,
  `id_office_purchase` int(11) DEFAULT 0,
  `id_product_purchase` int(11) DEFAULT 0,
  `cost_purchase` double DEFAULT 0,
  `utility_purchase` text DEFAULT NULL,
  `price_purchase` double DEFAULT 0,
  `qty_purchase` int(11) DEFAULT 0,
  `invest_purchase` double DEFAULT 0,
  `date_created_purchase` date DEFAULT NULL,
  `date_updated_purchase` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `type_purchase` text DEFAULT 'compra',
  `status_purchase` text DEFAULT 'pendiente',
  `received_date_purchase` date DEFAULT NULL,
  `received_by_purchase` int(11) DEFAULT NULL,
  `may_product` double DEFAULT 0,
  `wholesale_quantity` int(11) DEFAULT 0,
  PRIMARY KEY (`id_purchase`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchases`
--

LOCK TABLES `purchases` WRITE;
/*!40000 ALTER TABLE `purchases` DISABLE KEYS */;
INSERT INTO `purchases` VALUES
(3,1,3,12,45,'100%25',90,5,225,'2025-10-30','2025-11-01 00:15:08','compra','pendiente',NULL,NULL,0,0),
(4,1,3,12,50,'100%25',100,15,750,'2025-10-31','2025-11-01 00:14:51','compra','pendiente',NULL,NULL,0,0),
(5,1,4,12,45,'100%25',90,20,900,'2025-10-31','2026-06-08 03:13:40','compra','pendiente',NULL,NULL,0,0),
(6,1,4,14,80,'100%25',160,25,2000,'2025-10-31','2026-06-08 03:13:40','compra','pendiente',NULL,NULL,0,0),
(7,1,3,14,80,'100%25',160,30,2400,'2025-10-31','2025-11-01 01:54:43','compra','pendiente',NULL,NULL,0,0),
(8,1,3,18,45,'100%25',90,15,675,'2025-11-04','2025-11-04 18:18:58','compra','pendiente',NULL,NULL,0,0),
(9,1,3,16,45,'100%25',90,15,675,'2025-11-04','2025-11-04 18:19:23','compra','pendiente',NULL,NULL,0,0),
(10,1,3,21,45,'100%25',90,15,675,'2025-11-04','2025-11-04 18:31:16','compra','pendiente',NULL,NULL,0,0),
(11,2,3,23,45,'100%25',90,5,225,'2025-11-04','2025-11-04 19:05:32','compra','pendiente',NULL,NULL,0,0),
(12,1,3,23,45,'100%25',95,10,450,'2025-11-08','2026-06-08 03:13:40','compra','pendiente',NULL,NULL,0,0),
(13,2,4,14,12313123,'100%',12,12,147757476,NULL,'2026-06-10 02:03:09','compra','pendiente',NULL,NULL,0,0),
(14,2,2,16,40,'100%',70,9,360,NULL,'2026-06-11 15:11:49','compra','pendiente',NULL,NULL,0,0),
(15,1,2,23,40,'100%',70,7,280,NULL,'2026-06-11 16:08:54','compra','pendiente',NULL,NULL,0,0);
/*!40000 ALTER TABLE `purchases` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER after_purchase_insert
AFTER INSERT ON purchases
FOR EACH ROW
BEGIN
    DECLARE office_id INT;
    
    
    SELECT id_office_warehouse INTO office_id 
    FROM warehouses 
    WHERE id_warehouse = NEW.id_office_purchase;
    
    
    IF office_id IS NULL THEN
        SET office_id = NEW.id_office_purchase;
    END IF;

    
    INSERT INTO product_inventory (id_product_inventory, id_office_inventory, stock_inventory, status_inventory, date_created_inventory)
    VALUES (NEW.id_product_purchase, office_id, NEW.qty_purchase, 1, CURDATE())
    ON DUPLICATE KEY UPDATE
        stock_inventory  = COALESCE(stock_inventory, 0) + NEW.qty_purchase,
        status_inventory = 1;

    
    UPDATE products
    SET stock_product = (
        SELECT COALESCE(SUM(stock_inventory), 0)
        FROM product_inventory
        WHERE id_product_inventory = NEW.id_product_purchase
    )
    WHERE id_product = NEW.id_product_purchase;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `qrs`
--

DROP TABLE IF EXISTS `qrs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `qrs` (
  `id_qr` int(11) NOT NULL AUTO_INCREMENT,
  `image_qr` text DEFAULT NULL,
  `id_office_qr` int(11) NOT NULL DEFAULT 0,
  `date_created_qr` date DEFAULT NULL,
  `date_updated_qr` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_qr`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qrs`
--

LOCK TABLES `qrs` WRITE;
/*!40000 ALTER TABLE `qrs` DISABLE KEYS */;
INSERT INTO `qrs` VALUES
(1,NULL,0,NULL,'2026-06-11 17:44:26'),
(2,'views/assets/files/qrs/qr_3_1781200905.png',3,NULL,'2026-06-11 18:01:45');
/*!40000 ALTER TABLE `qrs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quality_checks`
--

DROP TABLE IF EXISTS `quality_checks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quality_checks` (
  `id_qc` int(11) NOT NULL AUTO_INCREMENT,
  `id_production_qc` int(11) NOT NULL,
  `id_admin_qc` int(11) NOT NULL,
  `id_office_qc` int(11) NOT NULL,
  `result_qc` varchar(30) DEFAULT NULL,
  `qty_approved_qc` double DEFAULT 0,
  `qty_rejected_qc` double DEFAULT 0,
  `notes_qc` text DEFAULT NULL,
  `date_created_qc` date DEFAULT NULL,
  `date_updated_qc` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_qc`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quality_checks`
--

LOCK TABLES `quality_checks` WRITE;
/*!40000 ALTER TABLE `quality_checks` DISABLE KEYS */;
INSERT INTO `quality_checks` VALUES
(1,1,19,6,'aprobado',100,0,'','2026-06-08','2026-06-08 21:58:55');
/*!40000 ALTER TABLE `quality_checks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `raw_material_entries`
--

DROP TABLE IF EXISTS `raw_material_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `raw_material_entries` (
  `id_entry` int(11) NOT NULL AUTO_INCREMENT,
  `id_raw_material_entry` int(11) NOT NULL,
  `qty_entry` double NOT NULL,
  `unit_price_entry` double DEFAULT 0,
  `total_cost_entry` double DEFAULT 0,
  `lot_number_entry` text DEFAULT NULL,
  `supplier_entry` text DEFAULT NULL,
  `notes_entry` text DEFAULT NULL,
  `status_entry` text DEFAULT 'pendiente',
  `id_admin_entry` int(11) NOT NULL,
  `id_approved_by_entry` int(11) DEFAULT NULL,
  `date_entry` date DEFAULT NULL,
  `date_approved_entry` date DEFAULT NULL,
  `date_created_entry` date DEFAULT NULL,
  `date_updated_entry` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `type_entry` varchar(50) DEFAULT 'ingreso',
  `concept_entry` text DEFAULT NULL,
  PRIMARY KEY (`id_entry`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `raw_material_entries`
--

LOCK TABLES `raw_material_entries` WRITE;
/*!40000 ALTER TABLE `raw_material_entries` DISABLE KEYS */;
INSERT INTO `raw_material_entries` VALUES
(1,1,100,10,1000,'','J.E Bolivia',NULL,'aprobado',19,19,'2026-06-08','2026-06-08','2026-06-08','2026-06-08 21:57:51','ingreso',NULL);
/*!40000 ALTER TABLE `raw_material_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `raw_materials`
--

DROP TABLE IF EXISTS `raw_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `raw_materials` (
  `id_raw_material` int(11) NOT NULL AUTO_INCREMENT,
  `name_raw_material` text NOT NULL,
  `unit_raw_material` text NOT NULL,
  `measure_type` enum('weight','volume','unit') DEFAULT 'unit',
  `description_raw_material` text DEFAULT NULL,
  `stock_raw_material` double DEFAULT 0,
  `id_office_raw_material` int(11) NOT NULL,
  `id_admin_raw_material` int(11) NOT NULL,
  `status_raw_material` int(11) DEFAULT 1,
  `is_insumo` tinyint(1) DEFAULT 0,
  `date_created_raw_material` date DEFAULT NULL,
  `date_updated_raw_material` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `no_stock_raw_material` int(11) DEFAULT 0,
  `price_raw_material` double DEFAULT 0,
  PRIMARY KEY (`id_raw_material`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `raw_materials`
--

LOCK TABLES `raw_materials` WRITE;
/*!40000 ALTER TABLE `raw_materials` DISABLE KEYS */;
INSERT INTO `raw_materials` VALUES
(1,'Frutilla','kg','weight','',50,6,19,1,0,'2026-06-08','2026-06-08 21:58:32',0,0);
/*!40000 ALTER TABLE `raw_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipe_indirect_costs`
--

DROP TABLE IF EXISTS `recipe_indirect_costs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipe_indirect_costs` (
  `id_recipe_indirect` int(11) NOT NULL AUTO_INCREMENT,
  `id_recipe_indirect_recipe` int(11) NOT NULL,
  `id_type_indirect` int(11) NOT NULL,
  `amount_per_batch_indirect` double NOT NULL,
  `date_created_indirect` date DEFAULT NULL,
  `date_updated_indirect` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_recipe_indirect`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipe_indirect_costs`
--

LOCK TABLES `recipe_indirect_costs` WRITE;
/*!40000 ALTER TABLE `recipe_indirect_costs` DISABLE KEYS */;
/*!40000 ALTER TABLE `recipe_indirect_costs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipe_ingredients`
--

DROP TABLE IF EXISTS `recipe_ingredients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipe_ingredients` (
  `id_ingredient` int(11) NOT NULL AUTO_INCREMENT,
  `id_recipe_ingredient` int(11) NOT NULL,
  `id_raw_material_ingredient` int(11) NOT NULL,
  `qty_ingredient` double NOT NULL,
  `notes_ingredient` text DEFAULT NULL,
  `date_created_ingredient` date DEFAULT NULL,
  `date_updated_ingredient` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_ingredient`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipe_ingredients`
--

LOCK TABLES `recipe_ingredients` WRITE;
/*!40000 ALTER TABLE `recipe_ingredients` DISABLE KEYS */;
INSERT INTO `recipe_ingredients` VALUES
(1,1,1,100,NULL,'2026-06-08','2026-06-08 21:58:06');
/*!40000 ALTER TABLE `recipe_ingredients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipe_labor`
--

DROP TABLE IF EXISTS `recipe_labor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipe_labor` (
  `id_labor` int(11) NOT NULL AUTO_INCREMENT,
  `id_recipe_labor` int(11) NOT NULL,
  `description_labor` text NOT NULL,
  `type_labor` text DEFAULT 'fixed',
  `hours_labor` double DEFAULT 0,
  `cost_per_hour_labor` double DEFAULT 0,
  `fixed_cost_labor` double DEFAULT 0,
  `total_cost_labor` double DEFAULT 0,
  `date_created_labor` date DEFAULT NULL,
  `date_updated_labor` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_labor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipe_labor`
--

LOCK TABLES `recipe_labor` WRITE;
/*!40000 ALTER TABLE `recipe_labor` DISABLE KEYS */;
/*!40000 ALTER TABLE `recipe_labor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipes`
--

DROP TABLE IF EXISTS `recipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipes` (
  `id_recipe` int(11) NOT NULL AUTO_INCREMENT,
  `id_product_recipe` int(11) NOT NULL,
  `batch_size_recipe` double NOT NULL,
  `unit_batch_recipe` text DEFAULT NULL,
  `notes_recipe` text DEFAULT NULL,
  `id_office_recipe` int(11) NOT NULL,
  `id_admin_recipe` int(11) NOT NULL,
  `date_created_recipe` date DEFAULT NULL,
  `date_updated_recipe` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_recipe`),
  UNIQUE KEY `id_product_recipe` (`id_product_recipe`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipes`
--

LOCK TABLES `recipes` WRITE;
/*!40000 ALTER TABLE `recipes` DISABLE KEYS */;
INSERT INTO `recipes` VALUES
(1,27,100,'L',NULL,6,19,'2026-06-08','2026-06-08 21:58:06');
/*!40000 ALTER TABLE `recipes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_payments`
--

DROP TABLE IF EXISTS `sale_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_payments` (
  `id_sale_payment` int(11) NOT NULL AUTO_INCREMENT,
  `id_order_payment` int(11) NOT NULL,
  `method_payment` varchar(30) DEFAULT NULL,
  `reference_payment` varchar(255) DEFAULT NULL,
  `file_payment` varchar(255) DEFAULT NULL,
  `amount_payment` double DEFAULT 0,
  `id_admin_payment` int(11) DEFAULT 0,
  `date_created_payment` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_sale_payment`),
  KEY `idx_order_payment` (`id_order_payment`),
  KEY `idx_salepay_order` (`id_order_payment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_payments`
--

LOCK TABLES `sale_payments` WRITE;
/*!40000 ALTER TABLE `sale_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `id_sale` int(11) NOT NULL AUTO_INCREMENT,
  `id_order_sale` int(11) DEFAULT 0,
  `id_product_sale` int(11) DEFAULT 0,
  `tax_type_sale` text DEFAULT NULL,
  `tax_sale` double DEFAULT 0,
  `discount_sale` double DEFAULT 0,
  `qty_sale` int(11) DEFAULT 0,
  `subtotal_sale` double DEFAULT 0,
  `status_sale` text DEFAULT NULL,
  `id_admin_sale` int(11) DEFAULT 0,
  `id_client_sale` int(11) DEFAULT 0,
  `id_office_sale` int(11) DEFAULT 0,
  `date_created_sale` date DEFAULT NULL,
  `date_updated_sale` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_sale`),
  KEY `idx_sales_order` (`id_order_sale`),
  KEY `idx_sales_office` (`id_office_sale`)
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES
(41,37,12,'3%25',0,0,5,450,'Completada',1,16,3,'2025-10-31','2025-10-31 23:51:28'),
(43,39,12,'3%25',0,0,2,180,'Completada',1,14,3,'2025-10-31','2025-11-01 00:16:00'),
(64,51,12,'3%25',0,0,1,100,'Completada',1,16,3,'2025-10-31','2025-11-01 01:30:00'),
(65,53,14,'3%25',0,3,3,480,'Completada',1,16,3,'2025-10-31','2025-11-01 01:55:29'),
(66,54,12,'3%25',0,0,1,100,'Completada',14,15,3,'2025-10-31','2025-11-01 01:56:36'),
(67,54,14,'3%25',0,3,1,160,'Completada',14,15,3,'2025-10-31','2025-11-01 01:56:37'),
(68,55,14,'3%25',0,3,2,320,'Completada',14,14,3,'2025-10-31','2025-11-01 01:57:36'),
(69,55,12,'3%25',0,0,1,100,'Completada',14,14,3,'2025-10-31','2025-11-01 01:57:37'),
(70,56,14,'3%25',0,3,1,160,'Completada',14,14,3,'2025-10-31','2025-11-01 02:01:29'),
(71,57,14,'3%25',0,3,3,480,'Completada',1,16,3,'2025-11-03','2025-11-04 03:02:22'),
(72,57,12,'3%25',0,0,1,90,'Completada',1,16,3,'2025-11-03','2025-11-04 03:02:22'),
(77,59,14,'',0,3,1,160,'Completada',1,14,3,'2025-11-03','2025-11-04 03:26:42'),
(78,60,14,'',0,3,1,160,'Completada',1,15,3,'2025-11-03','2025-11-04 04:00:39'),
(79,61,14,'',0,3,1,160,'Completada',1,16,3,'2025-11-04','2025-11-04 04:10:08'),
(80,62,12,'',0,0,3,270,'Completada',1,15,3,'2025-11-04','2025-11-04 04:11:54'),
(81,63,12,'',0,0,4,360,'Completada',1,14,4,'2025-11-04','2026-06-08 03:13:40'),
(82,63,14,'',0,3,4,640,'Completada',1,14,4,'2025-11-04','2026-06-08 03:13:40'),
(83,64,21,'',0,0,5,450,'Completada',14,14,3,'2025-11-04','2025-11-04 18:45:25'),
(84,65,12,'',0,0,6,540,'Completada',14,16,3,'2025-11-04','2025-11-04 18:47:45'),
(85,66,21,'',0,3,2,180,'Completada',1,14,3,'2025-11-07','2025-11-07 20:39:22'),
(86,66,18,'',0,3,1,90,'Completada',1,14,3,'2025-11-07','2025-11-07 20:39:22'),
(88,67,21,'',0,3,1,90,'Completada',14,15,3,'2025-11-07','2025-11-07 21:27:03'),
(89,67,14,'',0,3,1,160,'Completada',14,15,3,'2025-11-07','2025-11-07 21:27:03'),
(90,67,16,'',0,0,1,90,'Completada',14,15,3,'2025-11-07','2025-11-07 21:27:03'),
(91,68,23,'',0,0,1,90,'Completada',14,14,3,'2025-11-07','2025-11-07 21:27:50'),
(92,69,18,'',0,3,1,90,'Completada',14,14,3,'2025-11-07','2025-11-07 21:39:03'),
(93,70,18,'',0,3,1,90,'Completada',14,14,3,'2025-11-07','2025-11-07 21:39:59'),
(94,71,18,'',0,3,1,90,'Completada',14,14,3,'2025-11-07','2025-11-07 21:40:47'),
(95,71,21,'',0,3,1,90,'Completada',14,14,3,'2025-11-07','2025-11-07 21:41:11'),
(96,72,21,'',0,3,1,90,'Completada',14,15,3,'2025-11-07','2025-11-07 21:45:02'),
(97,73,18,'',0,3,1,90,'Completada',14,16,3,'2025-11-07','2025-11-07 21:48:07'),
(98,74,21,'',0,3,1,90,'Completada',14,14,3,'2025-11-07','2025-11-07 21:54:13'),
(99,75,14,'',0,3,1,160,'Completada',14,15,3,'2025-11-07','2025-11-07 21:58:28'),
(100,76,14,'',0,3,1,160,'Completada',14,14,3,'2025-11-07','2025-11-07 21:59:34'),
(101,77,23,'',0,0,1,90,'Completada',14,15,3,'2025-11-07','2025-11-07 22:05:46'),
(102,78,21,'',0,3,1,90,'Completada',14,14,3,'2025-11-07','2025-11-07 22:12:08'),
(104,79,14,'',0,3,1,160,'Completada',14,15,3,'2025-11-07','2025-11-07 22:16:56'),
(105,80,14,'',0,3,1,160,'Completada',14,14,3,'2025-11-07','2025-11-07 22:17:12'),
(106,81,14,'',0,3,1,160,'Completada',14,15,3,'2025-11-07','2025-11-07 22:18:52'),
(107,82,16,'',0,0,1,90,'Completada',14,16,3,'2025-11-07','2025-11-07 22:30:51'),
(108,83,16,'',0,0,1,90,'Completada',14,15,3,'2025-11-07','2025-11-07 22:31:19'),
(109,85,14,'',0,3,1,160,'Completada',14,14,3,'2025-11-07','2025-11-07 22:31:50'),
(111,86,14,'',0,3,1,160,'Completada',14,14,3,'2025-11-08','2025-11-08 04:39:55'),
(112,86,16,'',0,0,1,90,'Completada',14,14,3,'2025-11-08','2025-11-08 04:39:55'),
(113,87,23,'',0,0,1,95,'Completada',14,15,3,'2025-11-08','2026-06-08 03:13:40'),
(115,89,23,'',0,0,1,90,'Completada',14,16,3,'2025-11-08','2025-11-08 04:57:47'),
(116,96,14,'0',0,0,1,100,'Pendiente',1,1,3,'2026-06-05','2026-06-05 19:09:14'),
(117,99,14,'0',0,3,7,543.2,'Pendiente',15,18,3,'2026-06-05','2026-06-05 19:37:30'),
(118,99,18,'0',0,3,3,130.95,'Pendiente',15,18,3,'2026-06-05','2026-06-05 19:37:38'),
(121,102,18,'0',0,3,1,45,'Pendiente',15,18,3,'2026-06-08','2026-06-08 20:56:16'),
(122,102,16,'0',0,0,1,45,'Pendiente',15,18,3,'2026-06-08','2026-06-08 20:56:17'),
(123,103,18,'0',0,3,2,87.3,'Completada',15,18,3,'2026-06-09','2026-06-09 17:49:28'),
(124,103,14,'0',0,3,1,80,'Pendiente',15,18,3,'2026-06-09','2026-06-09 17:49:03'),
(125,103,21,'0',0,3,1,45,'Pendiente',15,18,3,'2026-06-09','2026-06-09 17:49:04'),
(126,104,18,'0',0,3,1,45,'Completada',14,14,3,'2026-06-09','2026-06-09 17:56:38'),
(127,105,18,'0',0,3,1,45,'Completada',15,14,3,'2026-06-09','2026-06-10 01:59:40'),
(128,37,12,'1',21,1,1,1,'Completada',16,16,4,NULL,'2026-06-10 02:05:17'),
(129,107,14,'0',0,3,1,80,'Completada',15,18,3,'2026-06-11','2026-06-11 14:41:16'),
(130,108,14,'0',0,3,1,80,'Completada',14,16,3,'2026-06-11','2026-06-11 14:42:42'),
(131,109,18,'0',0,3,1,45,'Completada',14,18,3,'2026-06-11','2026-06-11 14:43:03'),
(132,111,16,'0',0,0,5,225,'Pendiente',20,18,7,'2026-06-11','2026-06-11 15:13:23'),
(133,112,16,'0',0,0,5,225,'Completada',20,15,7,'2026-06-11','2026-06-11 15:13:51'),
(134,113,16,'0',0,0,1,45,'Completada',20,18,7,'2026-06-11','2026-06-11 15:27:51'),
(135,114,16,'0',0,0,1,45,'Completada',20,18,7,'2026-06-11','2026-06-11 15:47:22'),
(136,115,16,'0',0,0,1,45,'Completada',20,15,7,'2026-06-11','2026-06-11 15:48:25'),
(137,116,16,'0',0,0,1,45,'Completada',20,15,7,'2026-06-11','2026-06-11 16:05:39'),
(138,117,18,'0',0,3,1,45,'Completada',15,14,3,'2026-06-11','2026-06-11 17:34:19'),
(139,118,14,'0',0,3,1,80,'Completada',14,14,3,'2026-06-11','2026-06-11 17:36:47'),
(140,119,14,'0',0,3,1,80,'Pendiente',15,14,3,'2026-06-11','2026-06-11 17:55:22'),
(141,120,16,'0',0,0,1,45,'Pendiente',15,18,3,'2026-06-11','2026-06-11 17:58:16'),
(142,121,16,'0',0,0,1,45,'Pendiente',15,18,3,'2026-06-11','2026-06-11 17:58:31'),
(143,122,18,'0',0,3,1,45,'Completada',15,18,3,'2026-06-11','2026-06-11 18:02:03'),
(144,123,18,'0',0,3,1,45,'Pendiente',15,18,3,'2026-06-11','2026-06-11 18:10:44');
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER after_sale_update
AFTER UPDATE ON sales
FOR EACH ROW
BEGIN
    DECLARE v_is_combo INT DEFAULT 0;

    IF NEW.status_sale = 'Completada' AND OLD.status_sale != 'Completada' THEN
        
        SELECT is_compound_product INTO v_is_combo FROM products WHERE id_product = NEW.id_product_sale;

        IF v_is_combo = 1 THEN
            
            UPDATE product_inventory pi
            INNER JOIN combo_items ci ON pi.id_product_inventory = ci.id_product_ci
            SET pi.stock_inventory = COALESCE(pi.stock_inventory, 0) - (ci.qty_ci * NEW.qty_sale)
            WHERE ci.id_combo_ci = NEW.id_product_sale AND pi.id_office_inventory = NEW.id_office_sale;
            
            
            UPDATE products p
            INNER JOIN combo_items ci ON p.id_product = ci.id_product_ci
            SET p.stock_product = (
                SELECT COALESCE(SUM(pi2.stock_inventory), 0)
                FROM product_inventory pi2
                WHERE pi2.id_product_inventory = p.id_product
            )
            WHERE ci.id_combo_ci = NEW.id_product_sale;

        ELSE
            
            UPDATE product_inventory
            SET stock_inventory = COALESCE(stock_inventory, 0) - NEW.qty_sale
            WHERE id_product_inventory = NEW.id_product_sale
              AND id_office_inventory   = NEW.id_office_sale;

            
            UPDATE products
            SET stock_product = (
                SELECT COALESCE(SUM(stock_inventory), 0)
                FROM product_inventory
                WHERE id_product_inventory = NEW.id_product_sale
            )
            WHERE id_product = NEW.id_product_sale;
        END IF;

        
        INSERT INTO warehouse_assignments
            (id_sub_warehouse_assignment, id_product_assignment, qty_assignment, id_dispatched_by, type_assignment, notes_assignment)
        SELECT id_sub_warehouse, NEW.id_product_sale, NEW.qty_sale, NEW.id_admin_sale,
               'venta', CONCAT('Venta en POS #', NEW.id_order_sale)
        FROM sub_warehouses
        WHERE id_admin_sub_warehouse  = NEW.id_admin_sale
          AND id_office_sub_warehouse = NEW.id_office_sale
        LIMIT 1;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `schema_migrations`
--

DROP TABLE IF EXISTS `schema_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `schema_migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `applied_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schema_migrations`
--

LOCK TABLES `schema_migrations` WRITE;
/*!40000 ALTER TABLE `schema_migrations` DISABLE KEYS */;
INSERT INTO `schema_migrations` VALUES
(1,1,'runtime_bootstrap','2026-06-08 20:16:46');
/*!40000 ALTER TABLE `schema_migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_transfers`
--

DROP TABLE IF EXISTS `stock_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transfers` (
  `id_transfer` int(11) NOT NULL AUTO_INCREMENT,
  `id_origin_office` int(11) NOT NULL,
  `id_dest_office` int(11) NOT NULL,
  `id_product_transfer` int(11) NOT NULL,
  `qty_transfer` double NOT NULL,
  `id_admin_transfer` int(11) NOT NULL,
  `notes_transfer` text DEFAULT NULL,
  `status_transfer` varchar(50) DEFAULT 'pendiente',
  `date_created_transfer` date DEFAULT NULL,
  `date_updated_transfer` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_transfer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_transfers`
--

LOCK TABLES `stock_transfers` WRITE;
/*!40000 ALTER TABLE `stock_transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sub_warehouses`
--

DROP TABLE IF EXISTS `sub_warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sub_warehouses` (
  `id_sub_warehouse` int(11) NOT NULL AUTO_INCREMENT,
  `id_admin_sub_warehouse` int(11) NOT NULL,
  `id_office_sub_warehouse` int(11) NOT NULL,
  `name_sub_warehouse` text DEFAULT NULL,
  `status_sub_warehouse` int(11) DEFAULT 1,
  `date_created_sub_warehouse` date DEFAULT NULL,
  `date_updated_sub_warehouse` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_sub_warehouse`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sub_warehouses`
--

LOCK TABLES `sub_warehouses` WRITE;
/*!40000 ALTER TABLE `sub_warehouses` DISABLE KEYS */;
INSERT INTO `sub_warehouses` VALUES
(1,0,7,'Sub-Almac�n de la Sucursal',1,'2026-06-11','2026-06-11 15:12:38');
/*!40000 ALTER TABLE `sub_warehouses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id_supplier` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_name` text DEFAULT NULL,
  `supplier_contact` text DEFAULT NULL,
  `date_created_supplier` date DEFAULT NULL,
  `date_updated_supplier` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status_supplier` int(11) DEFAULT 1,
  `type_supplier` varchar(50) DEFAULT 'ambos',
  `email_supplier` varchar(255) DEFAULT NULL,
  `ruc_supplier` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_supplier`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES
(1,'J.E Bolivia','79005900','2025-10-29','2025-10-29 20:17:42',1,'ambos',NULL,NULL),
(2,'Vital+Herbs','77959898','2025-10-29','2025-10-29 20:38:53',1,'ambos',NULL,NULL);
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_assignments`
--

DROP TABLE IF EXISTS `warehouse_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouse_assignments` (
  `id_assignment` int(11) NOT NULL AUTO_INCREMENT,
  `id_sub_warehouse_assignment` int(11) NOT NULL,
  `id_product_assignment` int(11) NOT NULL,
  `qty_assignment` double NOT NULL,
  `id_dispatched_by` int(11) NOT NULL,
  `id_request_assignment` int(11) DEFAULT NULL,
  `type_assignment` text DEFAULT 'despacho',
  `notes_assignment` text DEFAULT NULL,
  `date_created_assignment` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_assignment`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_assignments`
--

LOCK TABLES `warehouse_assignments` WRITE;
/*!40000 ALTER TABLE `warehouse_assignments` DISABLE KEYS */;
INSERT INTO `warehouse_assignments` VALUES
(1,1,16,5,21,NULL,'despacho','restablecer stock\n','2026-06-11 15:12:38'),
(2,1,16,4,21,NULL,'despacho','Asignación manual de stock desde Almacén','2026-06-11 15:27:34'),
(3,1,23,7,21,NULL,'despacho','Asignación manual de stock desde Almacén','2026-06-11 16:10:26');
/*!40000 ALTER TABLE `warehouse_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouses` (
  `id_warehouse` int(11) NOT NULL AUTO_INCREMENT,
  `title_warehouse` text DEFAULT NULL,
  `address_warehouse` text DEFAULT NULL,
  `phone_warehouse` text DEFAULT NULL,
  `id_office_warehouse` int(11) DEFAULT NULL,
  `date_created_warehouse` date DEFAULT NULL,
  PRIMARY KEY (`id_warehouse`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses`
--

LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
INSERT INTO `warehouses` VALUES
(1,'sadadsa','daas','111111111',6,'2026-06-11'),
(2,'Almacen remanaso','remanso, octavo anillo','12345678',8,'2026-06-11');
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER before_warehouse_insert
BEFORE INSERT ON warehouses
FOR EACH ROW
BEGIN
    INSERT INTO offices (title_office, address_office, phone_office, type_office, date_created_office)
    VALUES (NEW.title_warehouse, NEW.address_warehouse, NEW.phone_warehouse, 'almacen', CURDATE());
    SET NEW.id_office_warehouse = LAST_INSERT_ID();
    SET NEW.date_created_warehouse = CURDATE();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER before_warehouse_update
BEFORE UPDATE ON warehouses
FOR EACH ROW
BEGIN
    UPDATE offices
    SET title_office = NEW.title_warehouse,
        address_office = NEW.address_warehouse,
        phone_office = NEW.phone_warehouse
    WHERE id_office = OLD.id_office_warehouse;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER before_warehouse_delete
BEFORE DELETE ON warehouses
FOR EACH ROW
BEGIN
    DELETE FROM offices WHERE id_office = OLD.id_office_warehouse;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `waste_packaged`
--

DROP TABLE IF EXISTS `waste_packaged`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `waste_packaged` (
  `id_waste` int(11) NOT NULL AUTO_INCREMENT,
  `id_production_waste` int(11) NOT NULL,
  `id_product_waste` int(11) NOT NULL,
  `qty_waste` double NOT NULL,
  `id_office_waste` int(11) NOT NULL,
  `status_waste` varchar(50) DEFAULT 'en_almacen',
  `concept_waste` text DEFAULT NULL,
  `id_admin_waste` int(11) NOT NULL,
  `notes_waste` text DEFAULT NULL,
  `date_created_waste` date DEFAULT NULL,
  `date_updated_waste` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_waste`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `waste_packaged`
--

LOCK TABLES `waste_packaged` WRITE;
/*!40000 ALTER TABLE `waste_packaged` DISABLE KEYS */;
/*!40000 ALTER TABLE `waste_packaged` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-06-11 18:20:04
