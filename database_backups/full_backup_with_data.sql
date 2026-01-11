-- MySQL dump 10.13  Distrib 8.0.35, for Win64 (x86_64)
--
-- Host: localhost    Database: sistema_financiera
-- ------------------------------------------------------
-- Server version	8.0.35

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

--
-- Table structure for table `abonos_capital`
--

DROP TABLE IF EXISTS `abonos_capital`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `abonos_capital` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prestamo_id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `fecha` date NOT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `registrado_por` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `prestamo_id` (`prestamo_id`),
  KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `abonos_capital_ibfk_1` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `abonos_capital_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `abonos_capital`
--

LOCK TABLES `abonos_capital` WRITE;
/*!40000 ALTER TABLE `abonos_capital` DISABLE KEYS */;
/*!40000 ALTER TABLE `abonos_capital` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agencias`
--

DROP TABLE IF EXISTS `agencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agencias` (
  `id_agencia` int NOT NULL AUTO_INCREMENT,
  `nombre_agencia` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ciudad` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono_agencia` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('Activa','Inactiva') COLLATE utf8mb4_unicode_ci DEFAULT 'Activa',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `saldo_efectivo` decimal(15,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_agencia`),
  UNIQUE KEY `nombre_agencia` (`nombre_agencia`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agencias`
--

LOCK TABLES `agencias` WRITE;
/*!40000 ALTER TABLE `agencias` DISABLE KEYS */;
INSERT INTO `agencias` VALUES (1,'Oficina Principal-Comayagua','COMAYAGUA, HONDURAS, MEDIA CUADRA AL SUR DE MEGAPOSTERS','COMAYAGUA','2215-1010','Activa','2026-01-09 23:14:45',0.00),(2,'Sucursal--Santa Barbara ','Santa Barbara, contiguo a localidad el chile, frente a pizza Hut','Santa barbara','2215-1010','Activa','2026-01-09 23:36:46',0.00),(3,'Sucursal--Nacaome Valle','Frente a residencial las limas','Valle','2215-1010','Activa','2026-01-09 23:37:53',0.00);
/*!40000 ALTER TABLE `agencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `alertas_sistema`
--

DROP TABLE IF EXISTS `alertas_sistema`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alertas_sistema` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensaje` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_generacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('pendiente','revisado') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `agencia_id` int DEFAULT NULL,
  `usuario_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agencia_id` (`agencia_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `alertas_sistema_ibfk_1` FOREIGN KEY (`agencia_id`) REFERENCES `agencias` (`id_agencia`),
  CONSTRAINT `alertas_sistema_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alertas_sistema`
--

LOCK TABLES `alertas_sistema` WRITE;
/*!40000 ALTER TABLE `alertas_sistema` DISABLE KEYS */;
/*!40000 ALTER TABLE `alertas_sistema` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bancos`
--

DROP TABLE IF EXISTS `bancos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bancos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_banco` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_cuenta` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_cuenta` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Ahorro',
  `moneda` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'HNL',
  `saldo_actual` decimal(15,2) NOT NULL DEFAULT '0.00',
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_cuenta` (`numero_cuenta`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bancos`
--

LOCK TABLES `bancos` WRITE;
/*!40000 ALTER TABLE `bancos` DISABLE KEYS */;
INSERT INTO `bancos` VALUES (1,'BAC-HONDURAS','747026131','Ahorro','HNL',32306.67,'activo','2026-01-09 23:20:10','2026-01-11 19:28:25');
/*!40000 ALTER TABLE `bancos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cajas_agencias`
--

DROP TABLE IF EXISTS `cajas_agencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cajas_agencias` (
  `id_caja_agencia` int NOT NULL AUTO_INCREMENT,
  `id_agencia` int NOT NULL,
  `saldo_efectivo` decimal(15,2) DEFAULT '0.00',
  `saldo_caja_operativa` decimal(15,2) DEFAULT '0.00',
  `ultima_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_caja_agencia`),
  KEY `id_agencia` (`id_agencia`),
  CONSTRAINT `cajas_agencias_ibfk_1` FOREIGN KEY (`id_agencia`) REFERENCES `agencias` (`id_agencia`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cajas_agencias`
--

LOCK TABLES `cajas_agencias` WRITE;
/*!40000 ALTER TABLE `cajas_agencias` DISABLE KEYS */;
INSERT INTO `cajas_agencias` VALUES (1,1,0.00,0.00,'2026-01-11 17:07:26'),(2,3,0.00,0.00,'2026-01-11 18:12:56'),(3,2,0.00,0.00,'2026-01-11 18:10:02');
/*!40000 ALTER TABLE `cajas_agencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT NULL,
  `codigo_cliente` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_completo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_documento` enum('DNI','RUC','CE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DNI',
  `numero_documento` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` text COLLATE utf8mb4_unicode_ci,
  `departamento` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Departamento de residencia',
  `municipio` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Municipio de residencia',
  `barrio` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Barrio o colonia de residencia',
  `punto_referencia` text COLLATE utf8mb4_unicode_ci,
  `tipo_vivienda` enum('Propia','Alquilada','Familiar','Pagándola') COLLATE utf8mb4_unicode_ci DEFAULT 'Propia',
  `gps_coordenadas` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` enum('M','F') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'G??nero del cliente',
  `ocupacion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referencia_personal` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono_referencia` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_documento` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_dni_frontal` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ruta de la foto del DNI frontal',
  `foto_dni_posterior` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_perfil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_fachada_casa` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_recibo_servicio` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('activo','inactivo','en_mora','bloqueado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `cobrador_id` int DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_agencia` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_cliente` (`codigo_cliente`),
  UNIQUE KEY `numero_documento` (`numero_documento`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_codigo` (`codigo_cliente`),
  KEY `idx_documento` (`numero_documento`),
  KEY `idx_cobrador` (`cobrador_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_agencia_cliente` (`id_agencia`),
  KEY `idx_tipo_vivienda` (`tipo_vivienda`),
  KEY `idx_agencia` (`id_agencia`),
  CONSTRAINT `clientes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL,
  CONSTRAINT `clientes_ibfk_2` FOREIGN KEY (`cobrador_id`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL,
  CONSTRAINT `fk_clientes_agencia` FOREIGN KEY (`id_agencia`) REFERENCES `agencias` (`id_agencia`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,5,'CLI-2026-5460A0CC','Cliente Gestor 1 cliente 1','DNI','101910192937483','luicklgidiscua1@gmail.com','32450496',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1998-01-11','M','Comerciante',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'activo',5,NULL,'2026-01-11 15:37:39','2026-01-11 15:37:39',1),(2,5,'CLI-2026-4FFBE5D4','Cliente Asesor 1 B','DNI','101910192937483jvj','luigidiscua1@gmail.com','67048',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1998-01-11','M','Comerciante',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'activo',5,NULL,'2026-01-11 15:46:08','2026-01-11 15:46:08',1),(3,10,'CLI-2026-26925C8D','Cliente 2 A comayagua','DNI','Jdjdidbdj','luigidbsbsiskiscua1@gmail.com','67840',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1998-01-11','F','Comerciante',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'activo',10,NULL,'2026-01-11 15:47:37','2026-01-11 15:47:37',1),(4,10,'CLI-2026-13D20C85','Cliente 2 B','DNI','Kzbz','bB@habaoa','34840',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1998-01-11','M','Jzkzbdod',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'activo',10,NULL,'2026-01-11 15:48:36','2026-01-11 15:48:36',1),(5,11,'CLI-2026-DFE16160','Cliente Valle 1','DNI','Jzjxbzk','luigidiscua1@gmail.comka sosn','319439746',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2004-01-11','M','Comerciante',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'activo',11,NULL,'2026-01-11 15:49:49','2026-01-11 17:14:27',3),(6,11,'CLI-2026-959CBEAB','Cliente valle 2','DNI','Jzjzlnzbz','wv@habak','94',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1973-01-11','M','Comerciante',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'activo',11,NULL,'2026-01-11 15:50:44','2026-01-11 15:50:44',3),(7,18,'CLI-2026-99588C57','Cliente Santa Barbara 1','DNI','Kzbzoxnoz','luigidiscua1@gmail.comb@ozns','6494640',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1981-01-11','F','Comerciante',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'activo',18,NULL,'2026-01-11 15:51:54','2026-01-11 18:10:02',2);
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes_negocios`
--

DROP TABLE IF EXISTS `clientes_negocios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes_negocios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `nombre_negocio` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rubro` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `foto_negocio_1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_negocio_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_negocio_3` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_negocio_4` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_negocio_5` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doc_permiso_operaciones` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `garantia_descripcion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `garantia_valor` decimal(12,2) DEFAULT NULL,
  `foto_garantia_3` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_garantia_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_garantia_1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `clientes_negocios_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes_negocios`
--

LOCK TABLES `clientes_negocios` WRITE;
/*!40000 ALTER TABLE `clientes_negocios` DISABLE KEYS */;
/*!40000 ALTER TABLE `clientes_negocios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `colaboradores`
--

DROP TABLE IF EXISTS `colaboradores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `colaboradores` (
  `id_colaborador` int NOT NULL AUTO_INCREMENT,
  `dni` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_completo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_nacimiento` date NOT NULL,
  `genero` enum('Masculino','Femenino','Otro') COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion_residencia` text COLLATE utf8mb4_unicode_ci,
  `puesto_cargo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_agencia` int NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `sueldo_base` decimal(15,2) NOT NULL DEFAULT '0.00',
  `numero_cuenta_bancaria` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banco_receptor` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_cuenta` enum('Ahorro','Cheques','Nomina','Otro') COLLATE utf8mb4_unicode_ci DEFAULT 'Ahorro',
  `numero_seguro_social` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rtn_personal` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_laboral` enum('Activo','Vacaciones','Incapacitado','Suspendido','Despido','Renuncia') COLLATE utf8mb4_unicode_ci DEFAULT 'Activo',
  `creado_por` int DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ultima_modificacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sueldo_base_excepcion` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_colaborador`),
  UNIQUE KEY `dni` (`dni`),
  UNIQUE KEY `email` (`email`),
  KEY `dni_2` (`dni`),
  KEY `estado_laboral` (`estado_laboral`),
  KEY `id_agencia` (`id_agencia`),
  CONSTRAINT `fk_colaborador_agencia` FOREIGN KEY (`id_agencia`) REFERENCES `agencias` (`id_agencia`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `colaboradores`
--

LOCK TABLES `colaboradores` WRITE;
/*!40000 ALTER TABLE `colaboradores` DISABLE KEYS */;
INSERT INTO `colaboradores` VALUES (1,'0000','Admin Sistema','admin@sys.com','1990-01-01','Otro',NULL,NULL,'Admin',1,'2026-01-09',0.00,NULL,NULL,'Ahorro',NULL,NULL,'Activo',NULL,'2026-01-09 23:14:45','2026-01-09 23:14:45',NULL),(2,'0301199801400','GERENTE GENERAL','lediscua.c@gmail.com','1998-03-10','Masculino','77777777','barrio arriba','Gerente General',1,'2026-01-09',60000.00,'ASDASDA','BAC DE HONDURAS','Cheques','0301199801400','03011998014008','Activo',1,'2026-01-09 23:18:03','2026-01-09 23:18:03',NULL),(3,'03011998014001','ANALISTA','lediscua1.c@gmail.com','1998-03-10','Masculino','77777777','barrio arriba','Analista de Creditos',1,'2026-01-09',35000.00,'7585151010','BAC DE HONDURAS','Ahorro','0301198014001','03011998014008','Activo',2,'2026-01-09 23:22:20','2026-01-09 23:22:20',NULL),(4,'03011998014002','Operaciones-Comayagua1','lediscua2.c@gmail.com','1998-03-10','Masculino','77777777',NULL,'Ofic. de Operaciones',1,'2026-01-09',15000.00,'8411961681','BAC DE HONDURAS','Ahorro','0301198014001','03011998014002','Activo',2,'2026-01-09 23:23:47','2026-01-09 23:29:38',NULL),(5,'03011998014004','ASESOR COMAYAGUA 1','lediscua4.c@gmail.com','1998-03-10','Masculino','77777777',NULL,'Asesor de Créditos',1,'2026-01-09',15000.00,'15181181','BAC DE HONDURAS','Ahorro','0301198014001','03011998014004','Activo',2,'2026-01-09 23:27:23','2026-01-09 23:27:23',NULL),(6,'03011998014005','Desembolsador-Comayagua1','lediscua5.c@gmail.com','1998-03-10','Masculino','77777777','barrio arriba','Ofic. de desembolsos',1,'2026-01-09',15000.00,'7585151010','BAC DE HONDURAS','Ahorro','0301198014001','03011998014008','Activo',2,'2026-01-09 23:29:05','2026-01-09 23:29:05',NULL),(7,'03011998014006','Verificador comayagua 1','lediscuasdasd1.c@gmail.com','1998-03-10','Masculino','77777777','barrio arriba','Verificador de creditos',1,'2026-01-09',15000.00,'ASDASDA','BAC DE HONDURAS','Ahorro','0301198014001262','03011998014008121','Activo',2,'2026-01-09 23:33:22','2026-01-09 23:33:22',NULL),(8,'0301199801500','Operaciones--Nacaomevalle','led161iscua5.c@gmail.com','1998-03-10','Femenino','77777777','barrio arriba','Ofic. de Operaciones',3,'2026-01-09',15000.00,'8411961681','BAC DE HONDURAS','Ahorro','3030310','00806','Activo',2,'2026-01-09 23:41:13','2026-01-09 23:41:13',NULL),(9,'030111616681','Desembolsador-Comayagua2','lediscuwerwea4.c@gmail.com','1998-03-10','Masculino','77777777','barrio arriba','Ofic. de desembolsos',1,'2026-01-09',15000.00,'7585151010','BAC DE HONDURAS','Ahorro','erwerweeeeeee','03011151681','Activo',2,'2026-01-09 23:43:39','2026-01-09 23:43:39',NULL),(10,'03011998015002','Asesor Comayagua 2 ','lediscudasdasdasdasd1.c@gmail.com','1998-03-10','Masculino','99999999','barrio arriba','Asesor de Créditos',1,'2026-01-09',15000.00,'7585151010','BAC DE HONDURAS','Ahorro','0301198014001262','03011998014004','Activo',2,'2026-01-09 23:45:09','2026-01-09 23:45:09',NULL),(11,'181613165165161851','Asesor Nacaome 1','ledisasdasdasdcua5.c@gmail.com','1998-03-10','Masculino','99999999','asadasdas','Asesor de Créditos',3,'2026-01-09',15000.00,'ASDASDA','BAC DE HONDURAS','Ahorro','0301198014001262','03011998014002','Activo',2,'2026-01-09 23:47:12','2026-01-09 23:47:12',NULL),(12,'16161615616815','Desembolsador-valle 1','asdasd@gmail.com','1998-03-10','Masculino','99999999','barrio arriba','Ofic. de desembolsos',3,'2026-01-09',12123.00,'7585151010','BAC DE HONDURAS','Ahorro','0301198014001262','03011998014004','Activo',2,'2026-01-09 23:48:34','2026-01-09 23:48:34',NULL),(13,'6132181030','Supervisor de Agencia Valle','lediscuasdaasdasd1.c@gmail.com','1998-03-10','Masculino','77777777','barrio arriba','Sup. de Agencia',3,'2026-01-09',35000.00,'ASDASDA','BAC DE HONDURAS','Ahorro','erwerweeeeeee','03011151681','Activo',2,'2026-01-09 23:50:17','2026-01-09 23:50:17',NULL),(14,'912691269612961','Verificador Nacaome Valle','lediscawerquasdasd1.c@gmail.com','1998-03-10','Masculino','77777777','barrio arriba','Verificador de creditos',3,'2026-01-09',11100.00,'ASDASDA','BAC DE HONDURAS','Ahorro','0301198014001262','00806','Activo',2,'2026-01-09 23:51:45','2026-01-09 23:51:45',NULL),(15,'616161681','PROMOTOR COMAYAGUA 1','lASDASDASDediscua5.c@gmail.com','1998-03-10','Masculino','96981515','barrio arriba','Promotor de creditos',1,'2026-01-10',15000.00,'8946165','BAC DE HONDURAS','Ahorro','03011998014005','03011998012468','Activo',5,'2026-01-10 00:51:31','2026-01-10 00:51:31',NULL),(16,'468616','Supervisor Santa Barbara','leasdasdasdascua2.c@gmail.com','1998-03-10','Masculino','sadas','s','Sup. de Agencia',2,'2026-01-10',30000.00,'8946165','BAC DE HONDURAS','Ahorro','03011998014002','03011998014004','Activo',2,'2026-01-10 16:41:43','2026-01-10 16:41:43',NULL),(17,'4684656816','Operaciones Santa Barbara','DAVIDFLORdasdasdES@GAMIL.COM','1998-03-10','Masculino','96981515','barrio arriba','Ofic. de Operaciones',2,'2026-01-10',15000.00,'DSASDAS','BAC DE HONDURAS','Ahorro','0301199801246','03011998014008','Activo',2,'2026-01-10 16:43:17','2026-01-10 16:43:17',NULL),(18,'941620','Asesor Santa Barbara','ledisSADASasdasdDAScua2.c@gmail.com','1998-03-10','Masculino','96981515','barrio arriba','Asesor de Créditos',2,'2026-01-10',451385.00,'1010001','BAC DE HONDURAS','Ahorro','03011998014001','03011998014006','Activo',2,'2026-01-10 16:44:34','2026-01-10 16:44:34',NULL),(19,'03011998014006ASDASD','DESEMBOLSADOR SANTA BARBARA','DAVIDFLSDASCASDASDORES@GAMIL.COM','1998-03-10','Masculino','96981515','barrio arriba','Ofic. de desembolsos',2,'2026-01-10',123456.00,'8946165','BAC DE HONDURAS','Ahorro','asdgag','34ytqwer','Activo',17,'2026-01-10 17:07:41','2026-01-10 17:07:41',NULL);
/*!40000 ALTER TABLE `colaboradores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `config_planilla`
--

DROP TABLE IF EXISTS `config_planilla`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `config_planilla` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sueldo_base_general` decimal(10,2) DEFAULT '0.00',
  `minimo_clientes` int DEFAULT '60',
  `minimo_normalidad` decimal(5,2) DEFAULT '92.00',
  `tramos_comision` text COLLATE utf8mb4_unicode_ci,
  `escaladores_normalidad` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config_planilla`
--

LOCK TABLES `config_planilla` WRITE;
/*!40000 ALTER TABLE `config_planilla` DISABLE KEYS */;
INSERT INTO `config_planilla` VALUES (1,12000.00,60,92.00,'[{\"min\":0,\"max\":400000,\"monto\":0},{\"min\":400001,\"max\":500000,\"monto\":1000},{\"min\":500001,\"max\":750000,\"monto\":2300},{\"min\":750001,\"max\":999999999,\"monto\":3500}]','[{\"min\":92,\"max\":93,\"porcentaje\":50},{\"min\":93,\"max\":95,\"porcentaje\":75},{\"min\":95,\"max\":100,\"porcentaje\":100}]','2026-01-11 03:27:19');
/*!40000 ALTER TABLE `config_planilla` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuraciones`
--

DROP TABLE IF EXISTS `configuraciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuraciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clave` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('texto','numero','decimal','booleano','json') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'texto',
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`),
  KEY `idx_clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuraciones`
--

LOCK TABLES `configuraciones` WRITE;
/*!40000 ALTER TABLE `configuraciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `configuraciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `control_caja_diaria`
--

DROP TABLE IF EXISTS `control_caja_diaria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `control_caja_diaria` (
  `id_control` int NOT NULL AUTO_INCREMENT,
  `id_agencia` int NOT NULL,
  `id_usuario_apertura` int NOT NULL,
  `fecha_dia` date NOT NULL,
  `hora_apertura` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `saldo_apertura_sistema` decimal(15,2) NOT NULL,
  `saldo_apertura_fisico` decimal(15,2) NOT NULL,
  `id_usuario_cierre` int DEFAULT NULL,
  `hora_cierre` timestamp NULL DEFAULT NULL,
  `saldo_cierre_sistema` decimal(15,2) DEFAULT NULL,
  `saldo_cierre_fisico` decimal(15,2) DEFAULT NULL,
  `diferencia_cierre` decimal(15,2) DEFAULT '0.00',
  `estado` enum('Abierto','Cerrado') COLLATE utf8mb4_unicode_ci DEFAULT 'Abierto',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_control`),
  KEY `id_agencia` (`id_agencia`),
  KEY `id_usuario_apertura` (`id_usuario_apertura`),
  KEY `id_usuario_cierre` (`id_usuario_cierre`),
  CONSTRAINT `control_caja_diaria_ibfk_1` FOREIGN KEY (`id_agencia`) REFERENCES `agencias` (`id_agencia`),
  CONSTRAINT `control_caja_diaria_ibfk_2` FOREIGN KEY (`id_usuario_apertura`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `control_caja_diaria_ibfk_3` FOREIGN KEY (`id_usuario_cierre`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `control_caja_diaria`
--

LOCK TABLES `control_caja_diaria` WRITE;
/*!40000 ALTER TABLE `control_caja_diaria` DISABLE KEYS */;
INSERT INTO `control_caja_diaria` VALUES (1,1,4,'2026-01-11','2026-01-11 16:41:20',0.00,0.00,4,'2026-01-11 18:56:46',0.00,0.00,0.00,'Cerrado','\n--- CIERRE ---\n'),(2,3,8,'2026-01-11','2026-01-11 17:10:28',0.00,0.00,8,'2026-01-11 18:39:06',0.00,0.00,0.00,'Cerrado','\n--- CIERRE ---\n'),(3,2,17,'2026-01-11','2026-01-11 18:09:28',0.00,0.00,17,'2026-01-11 19:04:10',0.00,0.00,0.00,'Cerrado','\n--- CIERRE ---\n');
/*!40000 ALTER TABLE `control_caja_diaria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuadres_asesores`
--

DROP TABLE IF EXISTS `cuadres_asesores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cuadres_asesores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_asesor` int NOT NULL,
  `id_agencia` int NOT NULL,
  `fecha_cuadre` date NOT NULL,
  `monto_recaudado` decimal(10,2) NOT NULL DEFAULT '0.00',
  `monto_entregado` decimal(10,2) NOT NULL DEFAULT '0.00',
  `monto_efectivo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `monto_banco` decimal(10,2) NOT NULL DEFAULT '0.00',
  `banco_id` int DEFAULT NULL,
  `referencia_banco` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bloqueado` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 = bloqueado para cobros, 0 = desbloqueado',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `id_usuario_registro` int NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_asesor_fecha` (`id_asesor`,`fecha_cuadre`),
  KEY `idx_agencia` (`id_agencia`),
  KEY `fk_cuadre_usuario` (`id_usuario_registro`),
  CONSTRAINT `fk_cuadre_agencia` FOREIGN KEY (`id_agencia`) REFERENCES `agencias` (`id_agencia`) ON DELETE CASCADE,
  CONSTRAINT `fk_cuadre_asesor` FOREIGN KEY (`id_asesor`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `fk_cuadre_usuario` FOREIGN KEY (`id_usuario_registro`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuadres_asesores`
--

LOCK TABLES `cuadres_asesores` WRITE;
/*!40000 ALTER TABLE `cuadres_asesores` DISABLE KEYS */;
INSERT INTO `cuadres_asesores` VALUES (1,11,3,'2026-01-11',14400.00,14400.00,0.00,0.00,NULL,NULL,1,'Cuadre realizado con 0 movimiento(s) [Previo: 14400 | Cierre: 0]',8,'2026-01-11 12:38:37'),(2,12,3,'2026-01-11',0.00,0.00,0.00,0.00,NULL,NULL,1,'Cuadre realizado con 0 movimiento(s) [Previo: 0 | Cierre: 0]',8,'2026-01-11 12:38:54'),(3,5,1,'2026-01-11',1500.00,1500.00,0.00,0.00,NULL,NULL,1,'Cuadre realizado con 0 movimiento(s) [Previo: 1406.67 | Cierre: 0]',4,'2026-01-11 12:43:47'),(4,10,1,'2026-01-11',2906.67,2906.67,0.00,0.00,NULL,NULL,1,'Cuadre realizado con 0 movimiento(s) [Previo: 2906.67 | Cierre: 0]',4,'2026-01-11 12:56:06'),(5,6,1,'2026-01-11',0.00,0.00,0.00,0.00,NULL,NULL,1,'Cuadre realizado con 0 movimiento(s) [Previo: 0 | Cierre: 0]',4,'2026-01-11 12:56:18'),(6,9,1,'2026-01-11',0.00,0.00,0.00,0.00,NULL,NULL,1,'Cuadre realizado con 0 movimiento(s) [Previo: 0 | Cierre: 0]',4,'2026-01-11 12:56:25'),(7,18,2,'2026-01-11',2800.00,2800.00,0.00,0.00,NULL,NULL,1,'Cuadre realizado con 0 movimiento(s) [Previo: 2800 | Cierre: 0]',17,'2026-01-11 13:03:54'),(8,19,2,'2026-01-11',0.00,0.00,0.00,0.00,NULL,NULL,1,'Cuadre realizado con 0 movimiento(s) [Previo: 0 | Cierre: 0]',17,'2026-01-11 13:04:04');
/*!40000 ALTER TABLE `cuadres_asesores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuotas`
--

DROP TABLE IF EXISTS `cuotas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cuotas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prestamo_id` int NOT NULL,
  `numero_cuota` int NOT NULL,
  `monto_cuota` decimal(10,2) NOT NULL,
  `monto_pagado` decimal(10,2) DEFAULT '0.00',
  `fecha_vencimiento` date NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `dias_mora` int DEFAULT '0',
  `monto_mora` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fecha_pago_real` datetime DEFAULT NULL,
  `usuario_cobro_id` int DEFAULT NULL,
  `capital_cuota` decimal(15,2) DEFAULT '0.00' COMMENT 'Parte de capital en esta cuota',
  `interes_cuota` decimal(15,2) DEFAULT '0.00' COMMENT 'Parte de interés en esta cuota',
  `gastos_cuota` decimal(15,2) DEFAULT '0.00' COMMENT 'Parte de gastos financieros en esta cuota',
  `comision_cuota` decimal(15,2) DEFAULT '0.00' COMMENT 'Parte de comisión de papelería en esta cuota',
  PRIMARY KEY (`id`),
  KEY `idx_prestamo` (`prestamo_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_vencimiento` (`fecha_vencimiento`),
  CONSTRAINT `cuotas_ibfk_1` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuotas`
--

LOCK TABLES `cuotas` WRITE;
/*!40000 ALTER TABLE `cuotas` DISABLE KEYS */;
INSERT INTO `cuotas` VALUES (1,1,1,152.50,152.50,'2026-01-12','pagada',0,0.00,'2026-01-11 17:08:49','2026-01-11 18:23:11','2026-01-11 12:23:11',5,125.00,10.00,10.00,7.50),(2,1,2,147.50,147.50,'2026-01-13','pagada',0,0.00,'2026-01-11 17:08:49','2026-01-11 18:23:11','2026-01-11 12:23:11',5,120.91,9.67,9.67,7.25),(3,1,3,152.50,0.00,'2026-01-14','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(4,1,4,152.50,0.00,'2026-01-15','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(5,1,5,152.50,0.00,'2026-01-16','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(6,1,6,152.50,0.00,'2026-01-19','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(7,1,7,152.50,0.00,'2026-01-20','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(8,1,8,152.50,0.00,'2026-01-21','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(9,1,9,152.50,0.00,'2026-01-22','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(10,1,10,152.50,0.00,'2026-01-23','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(11,1,11,152.50,0.00,'2026-01-26','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(12,1,12,152.50,0.00,'2026-01-27','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(13,1,13,152.50,0.00,'2026-01-28','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(14,1,14,152.50,0.00,'2026-01-29','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(15,1,15,152.50,0.00,'2026-01-30','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(16,1,16,152.50,0.00,'2026-02-02','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(17,1,17,152.50,0.00,'2026-02-03','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(18,1,18,152.50,0.00,'2026-02-04','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(19,1,19,152.50,0.00,'2026-02-05','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(20,1,20,152.50,0.00,'2026-02-06','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(21,1,21,152.50,0.00,'2026-02-09','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(22,1,22,152.50,0.00,'2026-02-10','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(23,1,23,152.50,0.00,'2026-02-11','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(24,1,24,152.50,0.00,'2026-02-12','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(25,1,25,152.50,0.00,'2026-02-13','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(26,1,26,152.50,0.00,'2026-02-16','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(27,1,27,152.50,0.00,'2026-02-17','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(28,1,28,152.50,0.00,'2026-02-18','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(29,1,29,152.50,0.00,'2026-02-19','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(30,1,30,152.50,0.00,'2026-02-20','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(31,1,31,152.50,0.00,'2026-02-23','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(32,1,32,152.50,0.00,'2026-02-24','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(33,1,33,152.50,0.00,'2026-02-25','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(34,1,34,152.50,0.00,'2026-02-26','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(35,1,35,152.50,0.00,'2026-02-27','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(36,1,36,152.50,0.00,'2026-03-02','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(37,1,37,152.50,0.00,'2026-03-03','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(38,1,38,152.50,0.00,'2026-03-04','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(39,1,39,152.50,0.00,'2026-03-05','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(40,1,40,152.50,0.00,'2026-03-06','pendiente',0,0.00,'2026-01-11 17:08:49','2026-01-11 17:08:49',NULL,NULL,125.00,10.00,10.00,7.50),(41,3,1,1106.67,1106.67,'2026-01-26','pagada',0,0.00,'2026-01-11 17:08:53','2026-01-11 18:25:36','2026-01-11 12:25:36',10,666.67,160.00,160.00,120.00),(42,3,2,1106.67,0.00,'2026-02-09','pendiente',0,0.00,'2026-01-11 17:08:53','2026-01-11 17:08:53',NULL,NULL,666.67,160.00,160.00,120.00),(43,3,3,1106.67,0.00,'2026-02-23','pendiente',0,0.00,'2026-01-11 17:08:53','2026-01-11 17:08:53',NULL,NULL,666.67,160.00,160.00,120.00),(44,3,4,1106.67,0.00,'2026-03-09','pendiente',0,0.00,'2026-01-11 17:08:53','2026-01-11 17:08:53',NULL,NULL,666.67,160.00,160.00,120.00),(45,3,5,1106.67,0.00,'2026-03-23','pendiente',0,0.00,'2026-01-11 17:08:53','2026-01-11 17:08:53',NULL,NULL,666.67,160.00,160.00,120.00),(46,3,6,1106.67,0.00,'2026-04-06','pendiente',0,0.00,'2026-01-11 17:08:53','2026-01-11 17:08:53',NULL,NULL,666.67,160.00,160.00,120.00),(47,3,7,1106.67,0.00,'2026-04-20','pendiente',0,0.00,'2026-01-11 17:08:53','2026-01-11 17:08:53',NULL,NULL,666.67,160.00,160.00,120.00),(48,3,8,1106.67,0.00,'2026-05-04','pendiente',0,0.00,'2026-01-11 17:08:53','2026-01-11 17:08:53',NULL,NULL,666.67,160.00,160.00,120.00),(49,3,9,1106.67,0.00,'2026-05-18','pendiente',0,0.00,'2026-01-11 17:08:53','2026-01-11 17:08:53',NULL,NULL,666.67,160.00,160.00,120.00),(50,3,10,1106.67,0.00,'2026-06-01','pendiente',0,0.00,'2026-01-11 17:08:53','2026-01-11 17:08:53',NULL,NULL,666.67,160.00,160.00,120.00),(51,3,11,1106.67,0.00,'2026-06-15','pendiente',0,0.00,'2026-01-11 17:08:53','2026-01-11 17:08:53',NULL,NULL,666.67,160.00,160.00,120.00),(52,3,12,1106.63,0.00,'2026-06-29','pendiente',0,0.00,'2026-01-11 17:08:53','2026-01-11 17:08:53',NULL,NULL,666.63,160.00,160.00,120.00),(53,2,1,1108.33,1108.33,'2026-01-19','pagada',0,0.00,'2026-01-11 17:09:23','2026-01-11 18:23:22','2026-01-11 12:23:22',5,833.33,100.00,100.00,75.00),(54,2,2,91.67,91.67,'2026-01-26','pagada',0,0.00,'2026-01-11 17:09:23','2026-01-11 18:23:22','2026-01-11 12:23:22',5,68.93,8.27,8.27,6.20),(55,2,3,1108.33,0.00,'2026-02-02','pendiente',0,0.00,'2026-01-11 17:09:23','2026-01-11 17:09:23',NULL,NULL,833.33,100.00,100.00,75.00),(56,2,4,1108.33,0.00,'2026-02-09','pendiente',0,0.00,'2026-01-11 17:09:23','2026-01-11 17:09:23',NULL,NULL,833.33,100.00,100.00,75.00),(57,2,5,1108.33,0.00,'2026-02-16','pendiente',0,0.00,'2026-01-11 17:09:23','2026-01-11 17:09:23',NULL,NULL,833.33,100.00,100.00,75.00),(58,2,6,1108.33,0.00,'2026-02-23','pendiente',0,0.00,'2026-01-11 17:09:23','2026-01-11 17:09:23',NULL,NULL,833.33,100.00,100.00,75.00),(59,2,7,1108.33,0.00,'2026-03-02','pendiente',0,0.00,'2026-01-11 17:09:23','2026-01-11 17:09:23',NULL,NULL,833.33,100.00,100.00,75.00),(60,2,8,1108.33,0.00,'2026-03-09','pendiente',0,0.00,'2026-01-11 17:09:23','2026-01-11 17:09:23',NULL,NULL,833.33,100.00,100.00,75.00),(61,2,9,1108.33,0.00,'2026-03-16','pendiente',0,0.00,'2026-01-11 17:09:23','2026-01-11 17:09:23',NULL,NULL,833.33,100.00,100.00,75.00),(62,2,10,1108.33,0.00,'2026-03-23','pendiente',0,0.00,'2026-01-11 17:09:23','2026-01-11 17:09:23',NULL,NULL,833.33,100.00,100.00,75.00),(63,2,11,1108.33,0.00,'2026-03-30','pendiente',0,0.00,'2026-01-11 17:09:23','2026-01-11 17:09:23',NULL,NULL,833.33,100.00,100.00,75.00),(64,2,12,1108.37,0.00,'2026-04-06','pendiente',0,0.00,'2026-01-11 17:09:23','2026-01-11 17:09:23',NULL,NULL,833.37,100.00,100.00,75.00),(65,4,1,1743.00,1743.00,'2026-07-10','pagada',0,0.00,'2026-01-11 17:09:27','2026-01-11 18:25:50','2026-01-11 12:25:50',10,1050.00,252.00,252.00,189.00),(66,4,2,57.00,57.00,'2026-08-11','pagada',0,0.00,'2026-01-11 17:09:27','2026-01-11 18:25:50','2026-01-11 12:25:50',10,34.34,8.24,8.24,6.18),(67,4,3,1743.00,0.00,'2026-09-11','pendiente',0,0.00,'2026-01-11 17:09:27','2026-01-11 17:09:27',NULL,NULL,1050.00,252.00,252.00,189.00),(68,4,4,1743.00,0.00,'2026-10-12','pendiente',0,0.00,'2026-01-11 17:09:27','2026-01-11 17:09:27',NULL,NULL,1050.00,252.00,252.00,189.00),(69,4,5,1743.00,0.00,'2026-11-11','pendiente',0,0.00,'2026-01-11 17:09:27','2026-01-11 17:09:27',NULL,NULL,1050.00,252.00,252.00,189.00),(70,4,6,1743.00,0.00,'2026-12-11','pendiente',0,0.00,'2026-01-11 17:09:27','2026-01-11 17:09:27',NULL,NULL,1050.00,252.00,252.00,189.00),(71,6,1,1800.00,1800.00,'2026-01-19','pagada',0,0.00,'2026-01-11 17:12:43','2026-01-11 18:27:23','2026-01-11 12:27:23',11,1250.00,200.00,200.00,150.00),(72,6,2,1800.00,1800.00,'2026-01-26','pagada',0,0.00,'2026-01-11 17:12:43','2026-01-11 18:27:23','2026-01-11 12:27:23',11,1250.00,200.00,200.00,150.00),(73,6,3,1800.00,1800.00,'2026-02-02','pagada',0,0.00,'2026-01-11 17:12:43','2026-01-11 18:27:23','2026-01-11 12:27:23',11,1250.00,200.00,200.00,150.00),(74,6,4,1800.00,1800.00,'2026-02-09','pagada',0,0.00,'2026-01-11 17:12:43','2026-01-11 18:27:23','2026-01-11 12:27:23',11,1250.00,200.00,200.00,150.00),(75,6,5,1800.00,1800.00,'2026-02-16','pagada',0,0.00,'2026-01-11 17:12:43','2026-01-11 18:27:23','2026-01-11 12:27:23',11,1250.00,200.00,200.00,150.00),(76,6,6,1800.00,1800.00,'2026-02-23','pagada',0,0.00,'2026-01-11 17:12:43','2026-01-11 18:27:23','2026-01-11 12:27:23',11,1250.00,200.00,200.00,150.00),(77,6,7,1800.00,1800.00,'2026-03-02','pagada',0,0.00,'2026-01-11 17:12:43','2026-01-11 18:27:23','2026-01-11 12:27:23',11,1250.00,200.00,200.00,150.00),(78,6,8,1800.00,1800.00,'2026-03-09','pagada',0,0.00,'2026-01-11 17:12:43','2026-01-11 18:27:23','2026-01-11 12:27:23',11,1250.00,200.00,200.00,150.00),(79,6,9,1800.00,0.00,'2026-03-16','pendiente',0,0.00,'2026-01-11 17:12:43','2026-01-11 17:12:43',NULL,NULL,1250.00,200.00,200.00,150.00),(80,6,10,1800.00,0.00,'2026-03-23','pendiente',0,0.00,'2026-01-11 17:12:43','2026-01-11 17:12:43',NULL,NULL,1250.00,200.00,200.00,150.00),(81,6,11,1800.00,0.00,'2026-03-30','pendiente',0,0.00,'2026-01-11 17:12:43','2026-01-11 17:12:43',NULL,NULL,1250.00,200.00,200.00,150.00),(82,6,12,1800.00,0.00,'2026-04-06','pendiente',0,0.00,'2026-01-11 17:12:43','2026-01-11 17:12:43',NULL,NULL,1250.00,200.00,200.00,150.00),(83,6,13,1800.00,0.00,'2026-04-13','pendiente',0,0.00,'2026-01-11 17:12:43','2026-01-11 17:12:43',NULL,NULL,1250.00,200.00,200.00,150.00),(84,6,14,1800.00,0.00,'2026-04-20','pendiente',0,0.00,'2026-01-11 17:12:43','2026-01-11 17:12:43',NULL,NULL,1250.00,200.00,200.00,150.00),(85,6,15,1800.00,0.00,'2026-04-27','pendiente',0,0.00,'2026-01-11 17:12:43','2026-01-11 17:12:43',NULL,NULL,1250.00,200.00,200.00,150.00),(86,6,16,1800.00,0.00,'2026-05-04','pendiente',0,0.00,'2026-01-11 17:12:43','2026-01-11 17:12:43',NULL,NULL,1250.00,200.00,200.00,150.00),(87,7,1,2766.67,2766.67,'2026-01-19','pagada',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:27:53','2026-01-11 12:27:53',18,1666.67,400.00,400.00,300.00),(88,7,2,33.33,33.33,'2026-01-26','pagada',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:27:53','2026-01-11 12:27:53',18,20.08,4.82,4.82,3.61),(89,7,3,2766.67,0.00,'2026-02-02','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(90,7,4,2766.67,0.00,'2026-02-09','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(91,7,5,2766.67,0.00,'2026-02-16','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(92,7,6,2766.67,0.00,'2026-02-23','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(93,7,7,2766.67,0.00,'2026-03-02','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(94,7,8,2766.67,0.00,'2026-03-09','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(95,7,9,2766.67,0.00,'2026-03-16','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(96,7,10,2766.67,0.00,'2026-03-23','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(97,7,11,2766.67,0.00,'2026-03-30','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(98,7,12,2766.67,0.00,'2026-04-06','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(99,7,13,2766.67,0.00,'2026-04-13','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(100,7,14,2766.67,0.00,'2026-04-20','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(101,7,15,2766.67,0.00,'2026-04-27','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(102,7,16,2766.67,0.00,'2026-05-04','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(103,7,17,2766.67,0.00,'2026-05-11','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(104,7,18,2766.67,0.00,'2026-05-18','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(105,7,19,2766.67,0.00,'2026-05-25','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(106,7,20,2766.67,0.00,'2026-06-01','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(107,7,21,2766.67,0.00,'2026-06-08','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(108,7,22,2766.67,0.00,'2026-06-15','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(109,7,23,2766.67,0.00,'2026-06-22','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.67,400.00,400.00,300.00),(110,7,24,2766.59,0.00,'2026-06-29','pendiente',0,0.00,'2026-01-11 18:10:42','2026-01-11 18:10:42',NULL,NULL,1666.59,400.00,400.00,300.00),(111,1,2,5.00,0.00,'2026-01-13','pendiente',0,0.00,'2026-01-11 18:23:11','2026-01-11 18:23:11',NULL,NULL,4.09,0.33,0.33,0.25),(112,2,2,1016.66,0.00,'2026-01-26','pendiente',0,0.00,'2026-01-11 18:23:22','2026-01-11 18:23:22',NULL,NULL,764.40,91.73,91.73,68.80),(113,4,2,1686.00,0.00,'2026-08-11','pendiente',0,0.00,'2026-01-11 18:25:50','2026-01-11 18:25:50',NULL,NULL,1015.66,243.76,243.76,182.82),(114,7,2,2733.34,0.00,'2026-01-26','pendiente',0,0.00,'2026-01-11 18:27:53','2026-01-11 18:27:53',NULL,NULL,1646.59,395.18,395.18,296.39);
/*!40000 ALTER TABLE `cuotas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historico_planillas`
--

DROP TABLE IF EXISTS `historico_planillas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historico_planillas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `colaborador_id` int NOT NULL,
  `mes` int NOT NULL,
  `anio` int NOT NULL,
  `fecha_generacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `sueldo_base` decimal(10,2) DEFAULT '0.00',
  `comision_calculada` decimal(10,2) DEFAULT '0.00',
  `gastos_campo` decimal(10,2) DEFAULT '0.00',
  `total_pagar` decimal(10,2) DEFAULT '0.00',
  `clientes_activos` int DEFAULT '0',
  `saldo_cartera` decimal(12,2) DEFAULT '0.00',
  `normalidad_porcentaje` decimal(5,2) DEFAULT '0.00',
  `detalle_calculo` text COLLATE utf8mb4_unicode_ci,
  `estado` enum('borrador','pagado') COLLATE utf8mb4_unicode_ci DEFAULT 'borrador',
  PRIMARY KEY (`id`),
  KEY `colaborador_id` (`colaborador_id`),
  CONSTRAINT `historico_planillas_ibfk_1` FOREIGN KEY (`colaborador_id`) REFERENCES `colaboradores` (`id_colaborador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historico_planillas`
--

LOCK TABLES `historico_planillas` WRITE;
/*!40000 ALTER TABLE `historico_planillas` DISABLE KEYS */;
/*!40000 ALTER TABLE `historico_planillas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ingresos_bancos_agencia`
--

DROP TABLE IF EXISTS `ingresos_bancos_agencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ingresos_bancos_agencia` (
  `id` int NOT NULL AUTO_INCREMENT,
  `banco_id` int NOT NULL,
  `agencia_id` int NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `referencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `saldo_anterior_banco` decimal(15,2) NOT NULL,
  `saldo_nuevo_banco` decimal(15,2) NOT NULL,
  `saldo_anterior_agencia` decimal(15,2) NOT NULL,
  `saldo_nuevo_agencia` decimal(15,2) NOT NULL,
  `realizado_por` int NOT NULL,
  `fecha_hora` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `realizado_por` (`realizado_por`),
  KEY `idx_banco` (`banco_id`),
  KEY `idx_agencia` (`agencia_id`),
  KEY `idx_fecha` (`fecha_hora`),
  CONSTRAINT `ingresos_bancos_agencia_ibfk_1` FOREIGN KEY (`banco_id`) REFERENCES `bancos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ingresos_bancos_agencia_ibfk_2` FOREIGN KEY (`agencia_id`) REFERENCES `agencias` (`id_agencia`) ON DELETE RESTRICT,
  CONSTRAINT `ingresos_bancos_agencia_ibfk_3` FOREIGN KEY (`realizado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingresos_bancos_agencia`
--

LOCK TABLES `ingresos_bancos_agencia` WRITE;
/*!40000 ALTER TABLE `ingresos_bancos_agencia` DISABLE KEYS */;
INSERT INTO `ingresos_bancos_agencia` VALUES (1,1,1,29300.00,'piso por DONDE NO HAY',100000.00,70700.00,0.00,29300.00,4,'2026-01-11 16:59:33','categoriua bancaria'),(2,1,3,30000.00,'CH-BAC-1885153',70700.00,40700.00,0.00,30000.00,8,'2026-01-11 17:11:25','ZxZxZx'),(3,1,2,40000.00,'CH-BAC-188515',40700.00,700.00,0.00,40000.00,17,'2026-01-11 18:09:48','AXaX');
/*!40000 ALTER TABLE `ingresos_bancos_agencia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lista_puestos`
--

DROP TABLE IF EXISTS `lista_puestos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lista_puestos` (
  `id_puesto` int NOT NULL AUTO_INCREMENT,
  `nombre_puesto` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('Activo','Inactivo') COLLATE utf8mb4_unicode_ci DEFAULT 'Activo',
  PRIMARY KEY (`id_puesto`),
  UNIQUE KEY `nombre_puesto` (`nombre_puesto`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lista_puestos`
--

LOCK TABLES `lista_puestos` WRITE;
/*!40000 ALTER TABLE `lista_puestos` DISABLE KEYS */;
INSERT INTO `lista_puestos` VALUES (1,'Gerente General','Activo'),(2,'Sup. de Agencia','Activo'),(3,'Asesor de Créditos','Activo'),(4,'Ofic. de desembolsos','Activo'),(5,'Atención al Cliente','Activo'),(6,'Guardia de Seguridad','Activo'),(7,'Verificador de creditos','Activo'),(8,'Soporte Técnico','Activo'),(9,'Sup. de Operaciones','Activo'),(10,'Promotor de creditos','Activo'),(11,'Analista de Creditos','Activo'),(12,'Ofic. de Operaciones','Activo');
/*!40000 ALTER TABLE `lista_puestos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs_actividad`
--

DROP TABLE IF EXISTS `logs_actividad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_actividad` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT NULL,
  `accion` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modulo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `datos_anteriores` json DEFAULT NULL,
  `datos_nuevos` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_accion` (`accion`),
  KEY `idx_modulo` (`modulo`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `logs_actividad_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=265 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs_actividad`
--

LOCK TABLES `logs_actividad` WRITE;
/*!40000 ALTER TABLE `logs_actividad` DISABLE KEYS */;
INSERT INTO `logs_actividad` VALUES (1,1,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-09 23:15:37'),(2,1,'create','colaborador','Colaborador creado: GERENTE GENERAL','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id_colaborador\": \"2\"}','2026-01-09 23:18:03'),(3,2,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-09 23:18:23'),(4,2,'create','colaborador','Colaborador creado: ANALISTA','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id_colaborador\": \"3\"}','2026-01-09 23:22:21'),(5,2,'create','colaborador','Colaborador creado: Operaciones','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id_colaborador\": \"4\"}','2026-01-09 23:23:47'),(6,2,'create','colaborador','Colaborador creado: ASESOR COMAYAGUA 1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id_colaborador\": \"5\"}','2026-01-09 23:27:23'),(7,2,'create','colaborador','Colaborador creado: Desembolsador-Comayagua1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id_colaborador\": \"6\"}','2026-01-09 23:29:05'),(8,2,'update','colaborador','Colaborador actualizado ID: 4','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"dni\": \"03011998014002\", \"email\": \"lediscua2.c@gmail.com\", \"genero\": \"Masculino\", \"telefono\": \"77777777\", \"creado_por\": 2, \"id_agencia\": 1, \"usuario_id\": 4, \"sueldo_base\": \"15000.00\", \"tipo_cuenta\": \"Ahorro\", \"puesto_cargo\": \"Ofic. de Operaciones\", \"rtn_personal\": \"03011998014002\", \"fecha_ingreso\": \"2026-01-09\", \"banco_receptor\": \"BAC DE HONDURAS\", \"estado_laboral\": \"Activo\", \"fecha_creacion\": \"2026-01-09 17:23:47\", \"id_colaborador\": 4, \"nombre_completo\": \"Operaciones\", \"fecha_nacimiento\": \"1998-03-10\", \"saldo_caja_virtual\": \"0.00\", \"ultima_modificacion\": \"2026-01-09 17:23:47\", \"direccion_residencia\": null, \"numero_seguro_social\": \"0301198014001\", \"numero_cuenta_bancaria\": \"8411961681\"}','{\"id\": \"4\", \"dni\": \"03011998014002\", \"email\": \"lediscua2.c@gmail.com\", \"genero\": \"Masculino\", \"id_rol\": \"5\", \"telefono\": \"77777777\", \"username\": \"operaciones\", \"id_agencia\": \"1\", \"usuario_id\": \"4\", \"sueldo_base\": 15000, \"tipo_cuenta\": \"Ahorro\", \"puesto_cargo\": \"Ofic. de Operaciones\", \"rtn_personal\": \"03011998014002\", \"crear_usuario\": true, \"fecha_ingreso\": \"2026-01-09\", \"banco_receptor\": \"BAC DE HONDURAS\", \"estado_laboral\": null, \"id_jefe_directo\": \"\", \"nombre_completo\": \"Operaciones-Comayagua1\", \"fecha_nacimiento\": \"1998-03-10\", \"direccion_residencia\": \"\", \"numero_seguro_social\": \"0301198014001\", \"numero_cuenta_bancaria\": \"8411961681\"}','2026-01-09 23:29:38'),(9,2,'create','colaborador','Colaborador creado: Verificador comayagua 1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id_colaborador\": \"7\"}','2026-01-09 23:33:22'),(10,2,'create','colaborador','Colaborador creado: Operaciones--Nacaomevalle','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id_colaborador\": \"8\"}','2026-01-09 23:41:13'),(11,2,'create','colaborador','Colaborador creado: Desembolsador-Comayagua2','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id_colaborador\": \"9\"}','2026-01-09 23:43:39'),(12,2,'create','colaborador','Colaborador creado: Asesor Comayagua 2 ','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id_colaborador\": \"10\"}','2026-01-09 23:45:09'),(13,2,'create','colaborador','Colaborador creado: Asesor Nacaome 1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id_colaborador\": \"11\"}','2026-01-09 23:47:12'),(14,2,'create','colaborador','Colaborador creado: Desembolsador-valle 1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id_colaborador\": \"12\"}','2026-01-09 23:48:34'),(15,2,'create','colaborador','Colaborador creado: Supervisor de Agencia Valle','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id_colaborador\": \"13\"}','2026-01-09 23:50:17'),(16,2,'create','colaborador','Colaborador creado: Verificador Nacaome Valle','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id_colaborador\": \"14\"}','2026-01-09 23:51:45'),(17,5,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-09 23:52:44'),(18,5,'create','clientes','Cliente creado: Cliente asesor 1','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,'{\"id\": 1, \"email\": null, \"barrio\": null, \"estado\": \"activo\", \"genero\": \"F\", \"telefono\": \"37946707\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"Comerciante\", \"created_at\": \"2026-01-09 17:53:37\", \"id_agencia\": 1, \"updated_at\": \"2026-01-09 17:53:37\", \"usuario_id\": 5, \"cobrador_id\": null, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-3A7C8D50\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"Cliente asesor 1\", \"fecha_nacimiento\": \"2008-01-09\", \"foto_dni_frontal\": null, \"numero_documento\": \"0836292\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-09 23:53:37'),(19,5,'create','clientes','Cliente creado: Cliente Asesor 1 Br','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,'{\"id\": 2, \"email\": null, \"barrio\": null, \"estado\": \"activo\", \"genero\": \"M\", \"telefono\": \"67#4848\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"Comerciante\", \"created_at\": \"2026-01-09 17:54:35\", \"id_agencia\": 1, \"updated_at\": \"2026-01-09 17:54:35\", \"usuario_id\": 5, \"cobrador_id\": null, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-6E008458\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"Cliente Asesor 1 Br\", \"fecha_nacimiento\": \"1992-01-09\", \"foto_dni_frontal\": null, \"numero_documento\": \"739292\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-09 23:54:35'),(20,5,'create','clientes','Cliente creado: Cliente Asesor 1 Cr','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,'{\"id\": 3, \"email\": null, \"barrio\": null, \"estado\": \"activo\", \"genero\": \"M\", \"telefono\": \"64848\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"Comerciante\", \"created_at\": \"2026-01-09 17:55:32\", \"id_agencia\": 1, \"updated_at\": \"2026-01-09 17:55:32\", \"usuario_id\": 5, \"cobrador_id\": null, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-3F7940E0\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"Cliente Asesor 1 Cr\", \"fecha_nacimiento\": \"2000-01-09\", \"foto_dni_frontal\": null, \"numero_documento\": \"161919\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-09 23:55:32'),(21,2,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-09 23:58:21'),(22,NULL,'login_failed','auth','Intento de login fallido: ASESOR2COMAYAGUA','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-09 23:58:34'),(23,10,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-09 23:58:46'),(24,10,'create','clientes','Cliente creado: Cliente asesor com 2 A','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id\": 4, \"email\": null, \"barrio\": null, \"estado\": \"activo\", \"genero\": \"M\", \"telefono\": \"3245-0496\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"COMERCIANTE\", \"created_at\": \"2026-01-09 18:00:11\", \"id_agencia\": 1, \"updated_at\": \"2026-01-09 18:00:11\", \"usuario_id\": 10, \"cobrador_id\": null, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-93F385D4\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"Cliente asesor com 2 A\", \"fecha_nacimiento\": \"1998-01-10\", \"foto_dni_frontal\": null, \"numero_documento\": \"1668115\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-10 00:00:11'),(25,10,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:26:59'),(26,3,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:27:07'),(27,3,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:27:37'),(28,4,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:27:42'),(29,4,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:30:52'),(30,NULL,'login_failed','auth','Intento de login fallido: asesor','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:31:01'),(31,5,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:31:06'),(32,10,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 00:45:20'),(33,5,'create','colaborador','Colaborador creado: PROMOTOR COMAYAGUA 1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id_colaborador\": \"15\"}','2026-01-10 00:51:31'),(34,5,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:51:37'),(35,15,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:51:40'),(36,15,'create','clientes','Cliente creado: PRUEBA CON PROMOTOR 1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id\": 5, \"email\": \"ledisSADASASDDAScua2.c@gmail.com\", \"barrio\": null, \"estado\": \"activo\", \"genero\": \"F\", \"telefono\": \"WERTWERTASD\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"COMERCIANTE\", \"created_at\": \"2026-01-09 18:52:15\", \"id_agencia\": 1, \"updated_at\": \"2026-01-09 18:52:15\", \"usuario_id\": 15, \"cobrador_id\": 15, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-D2730FAB\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"PRUEBA CON PROMOTOR 1\", \"fecha_nacimiento\": \"1998-03-10\", \"foto_dni_frontal\": null, \"numero_documento\": \"146816814\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-10 00:52:15'),(37,15,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:52:26'),(38,5,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:52:31'),(39,5,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:52:39'),(40,3,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:52:48'),(41,3,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:52:59'),(42,NULL,'login_failed','auth','Intento de login fallido: promotor','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:53:04'),(43,15,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:53:13'),(44,15,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:53:40'),(45,3,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:53:43'),(46,3,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:54:05'),(47,4,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:54:08'),(48,4,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 00:56:26'),(49,10,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 01:01:20'),(50,5,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 01:01:50'),(51,NULL,'login_failed','auth','Intento de login fallido: supcom1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 01:03:02'),(52,NULL,'login_failed','auth','Intento de login fallido: supcom','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 01:03:05'),(53,NULL,'login_failed','auth','Intento de login fallido: gerenecia','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 01:03:28'),(54,2,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 01:03:56'),(55,2,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 01:08:01'),(56,15,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 01:08:07'),(57,15,'create','clientes','Cliente creado: PRUEBA 2 CON PROMOTOR','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id\": 6, \"email\": null, \"barrio\": null, \"estado\": \"activo\", \"genero\": \"M\", \"telefono\": \"WERTWERT\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"COMERCIANTE\", \"created_at\": \"2026-01-09 19:08:42\", \"id_agencia\": 1, \"updated_at\": \"2026-01-09 19:08:42\", \"usuario_id\": 15, \"cobrador_id\": 15, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-E9E54633\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"PRUEBA 2 CON PROMOTOR\", \"fecha_nacimiento\": \"1998-03-10\", \"foto_dni_frontal\": null, \"numero_documento\": \"12312312\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-10 01:08:42'),(58,5,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 01:10:30'),(59,15,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 01:10:40'),(60,10,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 01:10:46'),(61,5,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 01:10:52'),(62,10,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 01:17:35'),(63,2,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 01:17:44'),(64,5,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 01:36:55'),(65,10,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 01:37:05'),(66,2,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 01:42:56'),(67,2,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 01:43:06'),(68,2,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 01:44:03'),(69,13,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 01:44:10'),(70,13,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 01:46:52'),(71,11,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 01:47:04'),(72,11,'create','clientes','Cliente creado: prueba valle A','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id\": 7, \"email\": null, \"barrio\": null, \"estado\": \"activo\", \"genero\": \"M\", \"telefono\": \"sdcazscac\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"COMERCIANTE\", \"created_at\": \"2026-01-09 19:47:52\", \"id_agencia\": 3, \"updated_at\": \"2026-01-09 19:47:52\", \"usuario_id\": 11, \"cobrador_id\": 11, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-AD51900D\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"prueba valle A\", \"fecha_nacimiento\": \"1998-03-10\", \"foto_dni_frontal\": null, \"numero_documento\": \"861652168\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-10 01:47:52'),(73,11,'create','clientes','Cliente creado: prueba valle B','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id\": 8, \"email\": null, \"barrio\": null, \"estado\": \"activo\", \"genero\": \"M\", \"telefono\": \"asdasdasd\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"COMERCIANTE\", \"created_at\": \"2026-01-09 19:48:58\", \"id_agencia\": 3, \"updated_at\": \"2026-01-09 19:48:58\", \"usuario_id\": 11, \"cobrador_id\": 11, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-A9E0FAF5\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"prueba valle B\", \"fecha_nacimiento\": \"1998-03-10\", \"foto_dni_frontal\": null, \"numero_documento\": \"sdasdas\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-10 01:48:58'),(74,11,'create','clientes','Cliente creado: prueba valle c','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id\": 9, \"email\": null, \"barrio\": null, \"estado\": \"activo\", \"genero\": \"F\", \"telefono\": \"asdasd\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"COMERCIANTE\", \"created_at\": \"2026-01-09 19:49:38\", \"id_agencia\": 3, \"updated_at\": \"2026-01-09 19:49:38\", \"usuario_id\": 11, \"cobrador_id\": 11, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-E960ECAB\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"prueba valle c\", \"fecha_nacimiento\": \"1998-03-10\", \"foto_dni_frontal\": null, \"numero_documento\": \"asdasdas\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-10 01:49:38'),(75,11,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 01:50:02'),(76,13,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 01:50:06'),(77,13,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 01:53:18'),(78,11,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 01:53:24'),(79,11,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 02:25:54'),(80,NULL,'login_failed','auth','Intento de login fallido: asesor','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 02:26:03'),(81,NULL,'login_failed','auth','Intento de login fallido: ASESOR2COMAYAGUA','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 02:26:11'),(82,NULL,'login_failed','auth','Intento de login fallido: ASESOR2COMAYAGUA','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 02:26:14'),(83,NULL,'login_failed','auth','Intento de login fallido: ASESOR2COMAYAGUA','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 02:26:19'),(84,5,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 02:26:27'),(85,5,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 03:32:17'),(86,2,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 03:32:31'),(87,2,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 05:02:52'),(88,5,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 05:02:58'),(89,5,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 05:03:17'),(90,11,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 05:03:23'),(91,11,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 05:04:05'),(92,13,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 14:58:07'),(93,2,'login_failed','auth','Contraseña incorrecta','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 15:03:20'),(94,2,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 15:03:24'),(95,2,'create','clientes','Cliente creado: Test Client','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id\": 10, \"email\": null, \"barrio\": null, \"estado\": \"activo\", \"genero\": null, \"telefono\": \"99999999\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"Comerciante\", \"created_at\": \"2026-01-10 09:18:44\", \"id_agencia\": 1, \"updated_at\": \"2026-01-10 09:18:44\", \"usuario_id\": 2, \"cobrador_id\": 2, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-4167EFE5\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"Test Client\", \"fecha_nacimiento\": \"1990-01-01\", \"foto_dni_frontal\": null, \"numero_documento\": \"1234123412345\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-10 15:18:44'),(96,13,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 16:36:19'),(97,4,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 16:36:23'),(98,4,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 16:39:27'),(99,2,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 16:39:47'),(100,2,'create','colaborador','Colaborador creado: Supervisor Santa Barbara','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id_colaborador\": \"16\"}','2026-01-10 16:41:43'),(101,2,'create','colaborador','Colaborador creado: Operaciones Santa Barbara','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id_colaborador\": \"17\"}','2026-01-10 16:43:17'),(102,2,'create','colaborador','Colaborador creado: Asesor Santa Barbara','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id_colaborador\": \"18\"}','2026-01-10 16:44:34'),(103,2,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 16:48:26'),(104,17,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 16:48:48'),(105,17,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 16:49:42'),(106,8,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 16:50:05'),(107,8,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 16:53:43'),(108,18,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 16:53:59'),(109,18,'create','clientes','Cliente creado: cliente santa barbara 1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id\": 11, \"email\": \"lediscuasdasa3.c@gmail.com\", \"barrio\": null, \"estado\": \"activo\", \"genero\": \"M\", \"telefono\": \"3\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"COMERCIANTE\", \"created_at\": \"2026-01-10 10:54:36\", \"id_agencia\": 2, \"updated_at\": \"2026-01-10 10:54:36\", \"usuario_id\": 18, \"cobrador_id\": 18, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-C4F0F11F\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"cliente santa barbara 1\", \"fecha_nacimiento\": \"1998-03-10\", \"foto_dni_frontal\": null, \"numero_documento\": \"asdasdasasda\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-10 16:54:36'),(110,18,'create','clientes','Cliente creado: cliente santa barbara 2','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id\": 12, \"email\": \"ledisasdasdcua3.c@gmail.com\", \"barrio\": null, \"estado\": \"activo\", \"genero\": \"M\", \"telefono\": \"asdasda\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"COMERCIANTE\", \"created_at\": \"2026-01-10 10:55:05\", \"id_agencia\": 2, \"updated_at\": \"2026-01-10 10:55:05\", \"usuario_id\": 18, \"cobrador_id\": 18, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-132DABD5\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"cliente santa barbara 2\", \"fecha_nacimiento\": \"1998-03-10\", \"foto_dni_frontal\": null, \"numero_documento\": \"asdasdfasfa\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-10 16:55:05'),(111,18,'create','clientes','Cliente creado: cliente santa barbara 3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id\": 13, \"email\": \"lediscuaasdas3.c@gmail.com\", \"barrio\": null, \"estado\": \"activo\", \"genero\": \"F\", \"telefono\": \"asdasd\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"COMERCIANTE\", \"created_at\": \"2026-01-10 10:55:40\", \"id_agencia\": 2, \"updated_at\": \"2026-01-10 10:55:40\", \"usuario_id\": 18, \"cobrador_id\": 18, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-2A0B8677\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"cliente santa barbara 3\", \"fecha_nacimiento\": \"1999-03-10\", \"foto_dni_frontal\": null, \"numero_documento\": \"8946116410\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-10 16:55:40'),(112,18,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 16:58:08'),(113,11,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 16:58:23'),(114,11,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 16:58:36'),(115,5,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 16:58:43'),(116,5,'update','clientes','Cliente actualizado: Cliente Asesor 1 A','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"id\": 3, \"email\": null, \"barrio\": null, \"estado\": \"activo\", \"genero\": \"M\", \"telefono\": \"64848\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"Comerciante\", \"created_at\": \"2026-01-09 17:55:32\", \"id_agencia\": 1, \"updated_at\": \"2026-01-09 18:39:37\", \"usuario_id\": 5, \"cobrador_id\": 5, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-3F7940E0\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"Cliente Asesor 1 Cr\", \"fecha_nacimiento\": \"2000-01-09\", \"foto_dni_frontal\": null, \"numero_documento\": \"161919\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','{\"id\": 3, \"email\": \"\", \"barrio\": \"\", \"estado\": \"activo\", \"genero\": \"M\", \"telefono\": \"64848\", \"direccion\": \"\", \"municipio\": \"\", \"ocupacion\": \"Comerciante\", \"created_at\": \"2026-01-09 17:55:32\", \"id_agencia\": 1, \"updated_at\": \"2026-01-10 10:59:15\", \"usuario_id\": 5, \"cobrador_id\": 5, \"foto_perfil\": null, \"departamento\": \"\", \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-3F7940E0\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"cobrador_nombre\": \"ASESOR COMAYAGUA 1\", \"gps_coordenadas\": \"\", \"nombre_completo\": \"Cliente Asesor 1 A\", \"fecha_nacimiento\": \"2000-01-09\", \"foto_dni_frontal\": null, \"numero_documento\": \"161919\", \"punto_referencia\": \"\", \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-10 16:59:15'),(117,5,'update','clientes','Cliente actualizado: Cliente Asesor 1 B','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"id\": 2, \"email\": null, \"barrio\": null, \"estado\": \"activo\", \"genero\": \"M\", \"telefono\": \"67#4848\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"Comerciante\", \"created_at\": \"2026-01-09 17:54:35\", \"id_agencia\": 1, \"updated_at\": \"2026-01-09 18:39:37\", \"usuario_id\": 5, \"cobrador_id\": 5, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-6E008458\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"Cliente Asesor 1 Br\", \"fecha_nacimiento\": \"1992-01-09\", \"foto_dni_frontal\": null, \"numero_documento\": \"739292\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','{\"id\": 2, \"email\": \"\", \"barrio\": \"\", \"estado\": \"activo\", \"genero\": \"M\", \"telefono\": \"67#4848\", \"direccion\": \"\", \"municipio\": \"\", \"ocupacion\": \"Comerciante\", \"created_at\": \"2026-01-09 17:54:35\", \"id_agencia\": 1, \"updated_at\": \"2026-01-10 10:59:22\", \"usuario_id\": 5, \"cobrador_id\": 5, \"foto_perfil\": null, \"departamento\": \"\", \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-6E008458\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"cobrador_nombre\": \"ASESOR COMAYAGUA 1\", \"gps_coordenadas\": \"\", \"nombre_completo\": \"Cliente Asesor 1 B\", \"fecha_nacimiento\": \"1992-01-09\", \"foto_dni_frontal\": null, \"numero_documento\": \"739292\", \"punto_referencia\": \"\", \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-10 16:59:22'),(118,5,'update','clientes','Cliente actualizado: Cliente asesor 1 C','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"id\": 1, \"email\": null, \"barrio\": null, \"estado\": \"activo\", \"genero\": \"F\", \"telefono\": \"37946707\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"Comerciante\", \"created_at\": \"2026-01-09 17:53:37\", \"id_agencia\": 1, \"updated_at\": \"2026-01-09 18:39:37\", \"usuario_id\": 5, \"cobrador_id\": 5, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-3A7C8D50\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"Cliente asesor 1\", \"fecha_nacimiento\": \"2008-01-09\", \"foto_dni_frontal\": null, \"numero_documento\": \"0836292\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','{\"id\": 1, \"email\": \"\", \"barrio\": \"\", \"estado\": \"activo\", \"genero\": \"F\", \"telefono\": \"37946707\", \"direccion\": \"\", \"municipio\": \"\", \"ocupacion\": \"Comerciante\", \"created_at\": \"2026-01-09 17:53:37\", \"id_agencia\": 1, \"updated_at\": \"2026-01-10 10:59:29\", \"usuario_id\": 5, \"cobrador_id\": 5, \"foto_perfil\": null, \"departamento\": \"\", \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-3A7C8D50\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"cobrador_nombre\": \"ASESOR COMAYAGUA 1\", \"gps_coordenadas\": \"\", \"nombre_completo\": \"Cliente asesor 1 C\", \"fecha_nacimiento\": \"2008-01-09\", \"foto_dni_frontal\": null, \"numero_documento\": \"0836292\", \"punto_referencia\": \"\", \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-10 16:59:29'),(119,5,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 17:02:20'),(120,3,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 17:02:29'),(121,3,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 17:04:45'),(122,17,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 17:05:01'),(123,17,'create','colaborador','Colaborador creado: DESEMBOLSADOR SANTA BARBARA','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id_colaborador\": \"19\"}','2026-01-10 17:07:41'),(124,17,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 17:08:20'),(125,4,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 17:08:25'),(126,4,'update','roles','Actualizó el rol: Asesor de Creditos','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 17:43:20'),(127,4,'create','roles','Creó el rol: Asesor','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 17:43:41'),(128,4,'update','roles','Actualizó el rol: Oficial de desembolsos','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 17:44:49'),(129,4,'update','roles','Actualizó el rol: Asesor','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 17:44:57'),(130,4,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 17:47:49'),(131,4,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 17:48:52'),(132,4,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:08:42'),(133,2,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:08:46'),(134,2,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:23:47'),(135,NULL,'login_failed','auth','Intento de login fallido: DESEMBOLSADOR','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:23:55'),(136,NULL,'login_failed','auth','Intento de login fallido: DESEMBOLSADORcomayagua1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:24:02'),(137,19,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:24:13'),(138,19,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:24:44'),(139,NULL,'login_failed','auth','Intento de login fallido: desembolsadorcm1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:24:55'),(140,6,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:25:00'),(141,6,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:26:10'),(142,9,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:26:14'),(143,9,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:27:36'),(144,5,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:27:41'),(145,5,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:28:03'),(146,19,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:28:13'),(147,19,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:28:32'),(148,17,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:28:37'),(149,NULL,'login_failed','auth','Intento de login fallido: desembolsador','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 18:32:53'),(150,6,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 18:33:22'),(151,6,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 18:40:38'),(152,19,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 18:41:10'),(153,17,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:43:07'),(154,15,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:43:11'),(155,15,'create','clientes','Cliente creado: cliente promotor 1 comayagua','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,'{\"id\": 14, \"email\": \"lediaSDasdsSADASDAScua2.c@gmail.com\", \"barrio\": null, \"estado\": \"activo\", \"genero\": \"F\", \"telefono\": \"asdasdasd\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"COMERCIANTE\", \"created_at\": \"2026-01-10 12:44:10\", \"id_agencia\": 1, \"updated_at\": \"2026-01-10 12:44:10\", \"usuario_id\": 15, \"cobrador_id\": 15, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-8F3AE8CE\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"cliente promotor 1 comayagua\", \"fecha_nacimiento\": \"1998-03-10\", \"foto_dni_frontal\": null, \"numero_documento\": \"asfefgasdvs\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-10 18:44:10'),(156,15,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:45:07'),(157,4,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 18:45:12'),(158,19,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 18:46:40'),(159,9,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 18:46:49'),(160,9,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 19:06:43'),(161,5,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 19:06:51'),(162,5,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 19:31:46'),(163,9,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 19:32:38'),(164,9,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 19:33:03'),(165,10,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 19:33:11'),(166,10,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 19:34:01'),(167,10,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 19:34:10'),(168,10,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 19:34:39'),(169,5,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 19:34:49'),(170,5,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 19:35:57'),(171,NULL,'login_failed','auth','Intento de login fallido: asesorsantabarbara1','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 19:36:08'),(172,18,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 19:36:25'),(173,18,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 19:46:13'),(174,5,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 19:46:22'),(175,4,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 20:04:47'),(176,5,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 20:04:56'),(177,10,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 20:49:03'),(178,5,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 21:00:01'),(179,17,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 21:00:11'),(180,10,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 21:00:51'),(181,19,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 21:01:02'),(182,19,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 21:07:25'),(183,18,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-10 21:07:34'),(184,17,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 21:08:51'),(185,4,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 21:08:57'),(186,4,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 21:21:01'),(187,17,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 21:21:06'),(188,17,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 21:21:24'),(189,2,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 21:21:33'),(190,2,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 21:43:15'),(191,8,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 21:43:20'),(192,8,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 21:43:42'),(193,8,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 21:44:30'),(194,8,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 21:50:46'),(195,17,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 21:50:53'),(196,17,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 22:11:20'),(197,4,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 22:11:25'),(198,4,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 22:25:33'),(199,2,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 22:25:37'),(200,2,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-10 22:26:02'),(201,2,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 01:54:11'),(202,2,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 14:45:50'),(203,5,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 15:36:44'),(204,5,'create','clientes','Cliente creado: Cliente Gestor 1 cliente 1','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,'{\"id\": 1, \"email\": \"luicklgidiscua1@gmail.com\", \"barrio\": null, \"estado\": \"activo\", \"genero\": \"M\", \"telefono\": \"32450496\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"Comerciante\", \"created_at\": \"2026-01-11 09:37:39\", \"id_agencia\": 1, \"updated_at\": \"2026-01-11 09:37:39\", \"usuario_id\": 5, \"cobrador_id\": 5, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-5460A0CC\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"Cliente Gestor 1 cliente 1\", \"fecha_nacimiento\": \"1998-01-11\", \"foto_dni_frontal\": null, \"numero_documento\": \"101910192937483\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-11 15:37:39'),(205,5,'create','clientes','Cliente creado: Cliente Asesor 1 B','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,'{\"id\": 2, \"email\": \"luigidiscua1@gmail.com\", \"barrio\": null, \"estado\": \"activo\", \"genero\": \"M\", \"telefono\": \"67048\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"Comerciante\", \"created_at\": \"2026-01-11 09:46:08\", \"id_agencia\": 1, \"updated_at\": \"2026-01-11 09:46:08\", \"usuario_id\": 5, \"cobrador_id\": 5, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-4FFBE5D4\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"Cliente Asesor 1 B\", \"fecha_nacimiento\": \"1998-01-11\", \"foto_dni_frontal\": null, \"numero_documento\": \"101910192937483jvj\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-11 15:46:08'),(206,5,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 15:46:40'),(207,10,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 15:46:50'),(208,10,'create','clientes','Cliente creado: Cliente 2 A comayagua','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,'{\"id\": 3, \"email\": \"luigidbsbsiskiscua1@gmail.com\", \"barrio\": null, \"estado\": \"activo\", \"genero\": \"F\", \"telefono\": \"67840\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"Comerciante\", \"created_at\": \"2026-01-11 09:47:37\", \"id_agencia\": 1, \"updated_at\": \"2026-01-11 09:47:37\", \"usuario_id\": 10, \"cobrador_id\": 10, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-26925C8D\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"Cliente 2 A comayagua\", \"fecha_nacimiento\": \"1998-01-11\", \"foto_dni_frontal\": null, \"numero_documento\": \"Jdjdidbdj\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-11 15:47:37'),(209,10,'create','clientes','Cliente creado: Cliente 2 B','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,'{\"id\": 4, \"email\": \"bB@habaoa\", \"barrio\": null, \"estado\": \"activo\", \"genero\": \"M\", \"telefono\": \"34840\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"Jzkzbdod\", \"created_at\": \"2026-01-11 09:48:36\", \"id_agencia\": 1, \"updated_at\": \"2026-01-11 09:48:36\", \"usuario_id\": 10, \"cobrador_id\": 10, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-13D20C85\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"Cliente 2 B\", \"fecha_nacimiento\": \"1998-01-11\", \"foto_dni_frontal\": null, \"numero_documento\": \"Kzbz\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-11 15:48:36'),(210,10,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 15:49:04'),(211,11,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 15:49:16'),(212,11,'create','clientes','Cliente creado: Cliente Valle 1','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,'{\"id\": 5, \"email\": \"luigidiscua1@gmail.comka sosn\", \"barrio\": null, \"estado\": \"activo\", \"genero\": \"M\", \"telefono\": \"319439746\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"Comerciante\", \"created_at\": \"2026-01-11 09:49:49\", \"id_agencia\": 3, \"updated_at\": \"2026-01-11 09:49:49\", \"usuario_id\": 11, \"cobrador_id\": 11, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-DFE16160\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"Cliente Valle 1\", \"fecha_nacimiento\": \"2004-01-11\", \"foto_dni_frontal\": null, \"numero_documento\": \"Jzjxbzk\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-11 15:49:50'),(213,11,'create','clientes','Cliente creado: Cliente valle 2','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,'{\"id\": 6, \"email\": \"wv@habak\", \"barrio\": null, \"estado\": \"activo\", \"genero\": \"M\", \"telefono\": \"94\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"Comerciante\", \"created_at\": \"2026-01-11 09:50:44\", \"id_agencia\": 3, \"updated_at\": \"2026-01-11 09:50:44\", \"usuario_id\": 11, \"cobrador_id\": 11, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-959CBEAB\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"Cliente valle 2\", \"fecha_nacimiento\": \"1973-01-11\", \"foto_dni_frontal\": null, \"numero_documento\": \"Jzjzlnzbz\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-11 15:50:44'),(214,11,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 15:51:10'),(215,18,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 15:51:22'),(216,18,'create','clientes','Cliente creado: Cliente Santa Barbara 1','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,'{\"id\": 7, \"email\": \"luigidiscua1@gmail.comb@ozns\", \"barrio\": null, \"estado\": \"activo\", \"genero\": \"F\", \"telefono\": \"6494640\", \"direccion\": null, \"municipio\": null, \"ocupacion\": \"Comerciante\", \"created_at\": \"2026-01-11 09:51:54\", \"id_agencia\": 2, \"updated_at\": \"2026-01-11 09:51:54\", \"usuario_id\": 18, \"cobrador_id\": 18, \"foto_perfil\": null, \"departamento\": null, \"observaciones\": null, \"tipo_vivienda\": null, \"codigo_cliente\": \"CLI-2026-99588C57\", \"foto_documento\": null, \"tipo_documento\": \"DNI\", \"gps_coordenadas\": null, \"nombre_completo\": \"Cliente Santa Barbara 1\", \"fecha_nacimiento\": \"1981-01-11\", \"foto_dni_frontal\": null, \"numero_documento\": \"Kzbzoxnoz\", \"punto_referencia\": null, \"foto_fachada_casa\": null, \"foto_dni_posterior\": null, \"referencia_personal\": null, \"telefono_referencia\": null, \"foto_recibo_servicio\": null}','2026-01-11 15:51:54'),(217,2,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 15:52:18'),(218,3,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 15:52:22'),(219,18,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 15:52:27'),(220,3,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 16:05:40'),(221,4,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 16:05:48'),(222,4,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 16:06:04'),(223,3,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 16:06:10'),(224,3,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 16:08:58'),(225,4,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 16:09:06'),(226,4,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 16:54:46'),(227,2,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 16:54:52'),(228,2,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 16:55:06'),(229,4,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 16:55:10'),(230,6,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 17:08:21'),(231,6,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 17:09:00'),(232,9,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 17:09:11'),(233,9,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 17:09:31'),(234,4,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 17:10:04'),(235,8,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 17:10:11'),(236,12,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 17:12:11'),(237,12,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 17:13:18'),(238,11,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 17:13:27'),(239,8,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 18:08:29'),(240,3,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 18:08:35'),(241,3,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 18:08:51'),(242,17,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 18:08:56'),(243,11,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 18:10:21'),(244,19,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 18:10:33'),(245,19,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 18:10:45'),(246,17,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 18:12:33'),(247,8,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 18:12:39'),(248,5,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 18:22:59'),(249,5,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 18:24:18'),(250,10,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 18:24:27'),(251,10,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 18:26:33'),(252,11,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 18:26:43'),(253,11,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 18:27:33'),(254,18,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 18:27:44'),(255,18,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 18:31:14'),(256,11,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 18:31:24'),(257,8,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 18:37:57'),(258,8,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 18:37:58'),(259,8,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 18:43:04'),(260,4,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 18:43:11'),(261,11,'logout','auth','Cierre de sesión','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 18:46:52'),(262,10,'login','auth','Inicio de sesión exitoso','192.168.1.15','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36',NULL,NULL,'2026-01-11 18:47:00'),(263,4,'logout','auth','Cierre de sesión','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 19:03:29'),(264,17,'login','auth','Inicio de sesión exitoso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',NULL,NULL,'2026-01-11 19:03:33');
/*!40000 ALTER TABLE `logs_actividad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimientos_bancarios`
--

DROP TABLE IF EXISTS `movimientos_bancarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos_bancarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `banco_id` int NOT NULL,
  `tipo_transaccion` enum('ingreso','egreso','traspaso_caja') COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `saldo_anterior` decimal(15,2) NOT NULL,
  `saldo_nuevo` decimal(15,2) NOT NULL,
  `referencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `realizado_por` int NOT NULL,
  `entidad_destino_tipo` enum('usuario','agencia','banco','externo') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entidad_destino_id` int DEFAULT NULL,
  `fecha_hora` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `realizado_por` (`realizado_por`),
  KEY `idx_banco` (`banco_id`),
  KEY `idx_fecha` (`fecha_hora`),
  CONSTRAINT `movimientos_bancarios_ibfk_1` FOREIGN KEY (`banco_id`) REFERENCES `bancos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `movimientos_bancarios_ibfk_2` FOREIGN KEY (`realizado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimientos_bancarios`
--

LOCK TABLES `movimientos_bancarios` WRITE;
/*!40000 ALTER TABLE `movimientos_bancarios` DISABLE KEYS */;
INSERT INTO `movimientos_bancarios` VALUES (1,1,'ingreso',100000.00,0.00,100000.00,NULL,'Inyección de Capital',2,NULL,NULL,'2026-01-11 15:44:33'),(2,1,'ingreso',10000.00,700.00,10700.00,NULL,'Depósito a cuenta. Ref: AWQAWA. AWDAS',8,NULL,NULL,'2026-01-11 18:12:56'),(3,1,'ingreso',14400.00,10700.00,25100.00,NULL,'Cuadre Asesor Asesor Nacaome 1. Ref: cuadre de asesor [AID:11]',8,NULL,NULL,'2026-01-11 18:38:33'),(4,1,'ingreso',1500.00,25100.00,26600.00,NULL,'Cuadre Asesor ASESOR COMAYAGUA 1. Ref: tx0102200 [AID:5]',4,NULL,NULL,'2026-01-11 18:43:43'),(5,1,'ingreso',2906.67,26600.00,29506.67,NULL,'Cuadre Asesor Asesor Comayagua 2 . Ref: 181681 [AID:10]',4,NULL,NULL,'2026-01-11 18:56:03'),(6,1,'ingreso',2800.00,29506.67,32306.67,NULL,'Cuadre Asesor Asesor Santa Barbara. Ref: 1651161 [AID:18]',17,NULL,NULL,'2026-01-11 19:03:51'),(7,1,'egreso',29300.00,100000.00,70700.00,'piso por DONDE NO HAY','Traslado de Fondos a Agencia (Corrección Auditoría)',4,'agencia',1,'2026-01-11 16:59:33'),(8,1,'egreso',30000.00,70700.00,40700.00,'CH-BAC-1885153','Traslado de Fondos a Agencia (Corrección Auditoría)',8,'agencia',3,'2026-01-11 17:11:25'),(9,1,'egreso',40000.00,40700.00,700.00,'CH-BAC-188515','Traslado de Fondos a Agencia (Corrección Auditoría)',17,'agencia',2,'2026-01-11 18:09:48');
/*!40000 ALTER TABLE `movimientos_bancarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimientos_internos_agencia`
--

DROP TABLE IF EXISTS `movimientos_internos_agencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos_internos_agencia` (
  `id_movimiento_interno` int NOT NULL AUTO_INCREMENT,
  `id_agencia` int NOT NULL,
  `tipo_movimiento` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `id_usuario_operador` int NOT NULL,
  `fecha_movimiento` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_movimiento_interno`),
  KEY `id_agencia` (`id_agencia`),
  KEY `id_usuario_operador` (`id_usuario_operador`),
  CONSTRAINT `movimientos_internos_agencia_ibfk_1` FOREIGN KEY (`id_agencia`) REFERENCES `agencias` (`id_agencia`),
  CONSTRAINT `movimientos_internos_agencia_ibfk_2` FOREIGN KEY (`id_usuario_operador`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimientos_internos_agencia`
--

LOCK TABLES `movimientos_internos_agencia` WRITE;
/*!40000 ALTER TABLE `movimientos_internos_agencia` DISABLE KEYS */;
INSERT INTO `movimientos_internos_agencia` VALUES (1,1,'Boveda a Caja',29300.00,4,'2026-01-11 17:06:26','Retiro de Bóveda a Caja para operaciones. desembolsos'),(2,1,'Caja a Ruta',5000.00,4,'2026-01-11 17:07:26','Desembolso préstamo #1 - Cliente: 1'),(3,1,'Caja a Ruta',8000.00,4,'2026-01-11 17:07:26','Desembolso préstamo #3 - Cliente: 3'),(4,1,'Caja a Ruta',10000.00,4,'2026-01-11 17:07:26','Desembolso préstamo #2 - Cliente: 2'),(5,1,'Caja a Ruta',6300.00,4,'2026-01-11 17:07:26','Desembolso préstamo #4 - Cliente: 4'),(6,3,'Boveda a Caja',30000.00,8,'2026-01-11 17:11:36','Retiro de Bóveda a Caja para operaciones. ZxZxZx'),(7,3,'Caja a Ruta',10000.00,8,'2026-01-11 17:11:52','Desembolso préstamo #5 - Cliente: 5'),(8,3,'Caja a Ruta',20000.00,8,'2026-01-11 17:11:52','Desembolso préstamo #6 - Cliente: 6'),(10,2,'Boveda a Caja',40000.00,17,'2026-01-11 18:09:54','Retiro de Bóveda a Caja para operaciones. 1523112'),(11,2,'Caja a Ruta',40000.00,17,'2026-01-11 18:10:02','Desembolso préstamo #7 - Cliente: 7'),(12,3,'Caja a Banco',10000.00,8,'2026-01-11 18:12:56','Depósito a BAC-HONDURAS Ref: AWQAWA. AWDAS'),(13,3,'Ingreso por Rechazo',10000.00,1,'2026-01-11 20:05:44','Corrección Sistema: Devolución Automática por Préstamo Rechazado #5');
/*!40000 ALTER TABLE `movimientos_internos_agencia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `negocios_garantias`
--

DROP TABLE IF EXISTS `negocios_garantias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `negocios_garantias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `negocio_id` int NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valor` decimal(12,2) DEFAULT '0.00',
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `negocio_id` (`negocio_id`),
  CONSTRAINT `negocios_garantias_ibfk_1` FOREIGN KEY (`negocio_id`) REFERENCES `clientes_negocios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `negocios_garantias`
--

LOCK TABLES `negocios_garantias` WRITE;
/*!40000 ALTER TABLE `negocios_garantias` DISABLE KEYS */;
/*!40000 ALTER TABLE `negocios_garantias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prestamos`
--

DROP TABLE IF EXISTS `prestamos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prestamos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int NOT NULL,
  `monto_capital` decimal(12,2) NOT NULL,
  `neto_entregar` decimal(10,2) DEFAULT NULL,
  `modalidad` enum('Diario','Semanal','Catorcenal','Mensual') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_prestamo` enum('Nuevo','Refinanciamiento','Readecuacion','Represtamo') COLLATE utf8mb4_unicode_ci DEFAULT 'Nuevo',
  `plazo_meses` int NOT NULL,
  `tasa_total` decimal(5,2) NOT NULL,
  `tasa_interes` decimal(5,2) DEFAULT '4.00',
  `tasa_gastos` decimal(5,2) DEFAULT '4.00',
  `tasa_comision` decimal(5,2) DEFAULT '3.00',
  `valor_cuota` decimal(12,2) NOT NULL,
  `total_a_pagar` decimal(12,2) NOT NULL,
  `estado` enum('Solicitado','En Análisis','Verificación de Campo','Pendiente de Operaciones','Aprobado','Rechazado','Activo','Finalizado','Refinanciado','Listo para Entrega','Rechazado en Ruta') COLLATE utf8mb4_unicode_ci DEFAULT 'Solicitado',
  `fecha_solicitud` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `comentario_analisis` text COLLATE utf8mb4_unicode_ci,
  `comentario_verificacion` text COLLATE utf8mb4_unicode_ci,
  `en_ruta_desembolso` tinyint(1) DEFAULT '0' COMMENT 'Si está en ruta para desembolso',
  `ruta_usuario_id` int DEFAULT NULL COMMENT 'ID del usuario que lleva el dinero',
  `asesor_creditos_id` int DEFAULT NULL COMMENT 'ID del asesor de créditos asignado para cobro',
  `oficial_desembolsos_id` int DEFAULT NULL COMMENT 'ID del oficial de desembolsos asignado para entrega',
  `ruta_fecha_salida` datetime DEFAULT NULL COMMENT 'Fecha/hora de salida a ruta',
  `fecha_desembolso` datetime DEFAULT NULL COMMENT 'Fecha/hora de desembolso efectivo',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `id_cliente` (`id_cliente`),
  KEY `fk_prestamos_asesor` (`asesor_creditos_id`),
  KEY `fk_prestamos_oficial` (`oficial_desembolsos_id`),
  CONSTRAINT `fk_prestamos_asesor` FOREIGN KEY (`asesor_creditos_id`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL,
  CONSTRAINT `fk_prestamos_oficial` FOREIGN KEY (`oficial_desembolsos_id`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL,
  CONSTRAINT `prestamos_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prestamos`
--

LOCK TABLES `prestamos` WRITE;
/*!40000 ALTER TABLE `prestamos` DISABLE KEYS */;
INSERT INTO `prestamos` VALUES (1,1,5000.00,5000.00,'Diario','Nuevo',2,11.00,4.00,3.50,3.50,152.50,6100.00,'Activo','2026-01-11 15:45:29','2026-01-11 15:45:29','2026-01-11 17:08:49','','',0,6,5,6,'2026-01-11 11:07:26','2026-01-11 11:08:49',''),(2,2,10000.00,10000.00,'Semanal','Nuevo',3,11.00,4.00,3.50,3.50,1108.33,13300.00,'Activo','2026-01-11 15:46:24','2026-01-11 15:46:24','2026-01-11 17:09:23','','',0,9,10,9,'2026-01-11 11:07:26','2026-01-11 11:09:23',''),(3,3,8000.00,8000.00,'Catorcenal','Nuevo',6,11.00,4.00,3.50,3.50,1106.67,13280.00,'Activo','2026-01-11 15:47:55','2026-01-11 15:47:55','2026-01-11 17:08:53','','',0,6,5,6,'2026-01-11 11:07:26','2026-01-11 11:08:53',''),(4,4,6300.00,6300.00,'Mensual','Nuevo',6,11.00,4.00,3.50,3.50,1743.00,10458.00,'Activo','2026-01-11 15:48:51','2026-01-11 15:48:51','2026-01-11 17:09:27','','',0,9,10,9,'2026-01-11 11:07:26','2026-01-11 11:09:27',''),(5,5,10000.00,10000.00,'Diario','Nuevo',3,11.00,4.00,3.50,3.50,221.67,13300.00,'Rechazado','2026-01-11 15:50:02','2026-01-11 15:50:02','2026-01-11 18:04:45','','',0,12,11,12,'2026-01-11 11:11:52',NULL,''),(6,6,20000.00,20000.00,'Semanal','Nuevo',4,11.00,4.00,3.50,3.50,1800.00,28800.00,'Activo','2026-01-11 15:51:04','2026-01-11 15:51:04','2026-01-11 17:12:43','','',0,12,11,12,'2026-01-11 11:11:52','2026-01-11 11:12:43',''),(7,7,40000.00,40000.00,'Semanal','Nuevo',6,11.00,4.00,3.50,3.50,2766.67,66400.00,'Activo','2026-01-11 15:52:11','2026-01-11 15:52:11','2026-01-11 18:10:42','','',0,19,18,19,'2026-01-11 12:10:02','2026-01-11 12:10:42','');
/*!40000 ALTER TABLE `prestamos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prestamos_comentarios`
--

DROP TABLE IF EXISTS `prestamos_comentarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prestamos_comentarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prestamo_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `etapa_flujo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `prestamo_id` (`prestamo_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `prestamos_comentarios_ibfk_1` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prestamos_comentarios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prestamos_comentarios`
--

LOCK TABLES `prestamos_comentarios` WRITE;
/*!40000 ALTER TABLE `prestamos_comentarios` DISABLE KEYS */;
/*!40000 ALTER TABLE `prestamos_comentarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id_rol` int NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `permisos` json NOT NULL,
  `estado` enum('Activo','Inactivo') COLLATE utf8mb4_unicode_ci DEFAULT 'Activo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `nombre_rol` (`nombre_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Administrador','Acceso total','[]','Activo','2026-01-09 23:14:45','2026-01-09 23:14:45'),(2,'Gerente','Gestión sucursal','[]','Activo','2026-01-09 23:14:45','2026-01-09 23:14:45'),(3,'Supervisor','Supervisión','[]','Activo','2026-01-09 23:14:45','2026-01-09 23:14:45'),(4,'Asesor','Gestión créditos','{\"caja\": {\"view\": true, \"open_cash\": true, \"close_cash\": true, \"return_bank\": true, \"return_vault\": true, \"view_balance\": true, \"view_movements\": true, \"withdraw_vault\": true, \"pull_funds_bank\": true}, \"pagos\": {\"view\": true, \"export\": true, \"view_details\": true, \"print_receipt\": true, \"view_schedule\": true, \"cancel_payment\": true, \"approve_payment\": true, \"register_payment\": true}, \"boveda\": {\"view\": true, \"pull_funds\": true, \"view_balance\": true, \"view_movements\": true, \"register_income\": true}, \"agencias\": {\"edit\": true, \"view\": true, \"create\": true, \"delete\": true, \"switch_agency\": true, \"view_collaborators\": true}, \"clientes\": {\"edit\": true, \"view\": true, \"create\": true, \"delete\": true, \"export\": true, \"view_loans\": true, \"print_ficha\": true, \"view_details\": true, \"change_status\": true, \"edit_business\": true, \"view_payments\": true, \"create_business\": true, \"delete_business\": true, \"upload_documents\": true}, \"cobrador\": {\"view\": true, \"export\": true, \"view_route\": true, \"collect_payment\": true}, \"reportes\": {\"view\": true, \"export_pdf\": true, \"export_excel\": true, \"report_loans\": true, \"report_payments\": true, \"report_treasury\": true, \"report_cash_flow\": true, \"report_portfolio\": true, \"report_delinquency\": true}, \"dashboard\": {\"view\": true}, \"garantias\": {\"edit\": true, \"view\": true, \"create\": true, \"delete\": true, \"view_documents\": true, \"upload_documents\": true}, \"prestamos\": {\"edit\": true, \"view\": true, \"cancel\": true, \"create\": true, \"delete\": true, \"export\": true, \"reject\": true, \"approve\": true, \"disburse\": true, \"view_details\": true, \"view_schedule\": true, \"print_contract\": true}, \"seguridad\": {\"view\": true, \"edit_role\": true, \"create_role\": true, \"delete_role\": true, \"manage_positions\": true, \"assign_permissions\": true}, \"tesoreria\": {\"view\": true, \"export\": true, \"edit_bank\": true, \"create_bank\": true, \"delete_bank\": true, \"view_balances\": true, \"inject_capital\": true, \"transfer_funds\": true}, \"operaciones\": {\"view\": true, \"view_dashboard\": true, \"prepare_delivery\": true, \"view_cash_balance\": true, \"view_disbursements\": true, \"view_vault_balance\": true, \"withdraw_vault_to_cash\": true}, \"referencias\": {\"edit\": true, \"view\": true, \"create\": true, \"delete\": true, \"verify\": true}, \"colaboradores\": {\"edit\": true, \"view\": true, \"create\": true, \"delete\": true, \"export\": true, \"assign_role\": true, \"change_status\": true, \"reset_password\": true}, \"configuracion\": {\"view\": true, \"edit_fees\": true, \"edit_system\": true, \"edit_general\": true, \"edit_interest_rates\": true}}','Activo','2026-01-09 23:14:45','2026-01-10 17:44:57'),(5,'Cajero','Caja','[]','Activo','2026-01-09 23:14:45','2026-01-09 23:14:45'),(6,'Cliente','Consulta','[]','Activo','2026-01-09 23:14:45','2026-01-09 23:14:45'),(7,'Oficial de desembolsos','','{\"caja\": {\"view\": true, \"open_cash\": true, \"close_cash\": true, \"return_bank\": true, \"return_vault\": true, \"view_balance\": true, \"view_movements\": true, \"withdraw_vault\": true, \"pull_funds_bank\": true}, \"pagos\": {\"view\": true, \"export\": true, \"view_details\": true, \"print_receipt\": true, \"view_schedule\": true, \"cancel_payment\": true, \"approve_payment\": true, \"register_payment\": true}, \"boveda\": {\"view\": true, \"pull_funds\": true, \"view_balance\": true, \"view_movements\": true, \"register_income\": true}, \"agencias\": {\"edit\": true, \"view\": true, \"create\": true, \"delete\": true, \"switch_agency\": true, \"view_collaborators\": true}, \"clientes\": {\"edit\": true, \"view\": true, \"create\": true, \"delete\": true, \"export\": true, \"view_loans\": true, \"print_ficha\": true, \"view_details\": true, \"change_status\": true, \"edit_business\": true, \"view_payments\": true, \"create_business\": true, \"delete_business\": true, \"upload_documents\": true}, \"cobrador\": {\"view\": true, \"export\": true, \"view_route\": true, \"collect_payment\": true}, \"reportes\": {\"view\": true, \"export_pdf\": true, \"export_excel\": true, \"report_loans\": true, \"report_payments\": true, \"report_treasury\": true, \"report_cash_flow\": true, \"report_portfolio\": true, \"report_delinquency\": true}, \"dashboard\": {\"view\": true}, \"garantias\": {\"edit\": true, \"view\": true, \"create\": true, \"delete\": true, \"view_documents\": true, \"upload_documents\": true}, \"prestamos\": {\"edit\": true, \"view\": true, \"cancel\": true, \"create\": true, \"delete\": true, \"export\": true, \"reject\": true, \"approve\": true, \"disburse\": true, \"view_details\": true, \"view_schedule\": true, \"print_contract\": true}, \"seguridad\": {\"view\": true, \"edit_role\": true, \"create_role\": true, \"delete_role\": true, \"manage_positions\": true, \"assign_permissions\": true}, \"tesoreria\": {\"view\": true, \"export\": true, \"edit_bank\": true, \"create_bank\": true, \"delete_bank\": true, \"view_balances\": true, \"inject_capital\": true, \"transfer_funds\": true}, \"operaciones\": {\"view\": true, \"view_dashboard\": true, \"prepare_delivery\": true, \"view_cash_balance\": true, \"view_disbursements\": true, \"view_vault_balance\": true, \"withdraw_vault_to_cash\": true}, \"referencias\": {\"edit\": true, \"view\": true, \"create\": true, \"delete\": true, \"verify\": true}, \"colaboradores\": {\"edit\": true, \"view\": true, \"create\": true, \"delete\": true, \"export\": true, \"assign_role\": true, \"change_status\": true, \"reset_password\": true}, \"configuracion\": {\"view\": true, \"edit_fees\": true, \"edit_system\": true, \"edit_general\": true, \"edit_interest_rates\": true}}','Activo','2026-01-10 17:43:41','2026-01-10 17:44:49');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `id_colaborador` int NOT NULL,
  `id_rol` int DEFAULT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_autorizacion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rol` enum('admin','cobrador','cliente','Supervisor','Gerente','cajero','asesor') COLLATE utf8mb4_unicode_ci DEFAULT 'cliente',
  `id_jefe_directo` int DEFAULT NULL,
  `saldo_caja_virtual` decimal(15,2) DEFAULT '0.00',
  `estado` enum('Activo','Inactivo','Suspendido') COLLATE utf8mb4_unicode_ci DEFAULT 'Activo',
  `token_sesion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_expiracion` datetime DEFAULT NULL,
  `ultimo_acceso` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `usuario` (`username`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_usuario` (`username`),
  KEY `idx_rol` (`rol`),
  KEY `idx_estado` (`estado`),
  KEY `idx_token` (`token_sesion`),
  KEY `fk_usuario_colaborador` (`id_colaborador`),
  KEY `fk_rol_usuario` (`id_rol`),
  CONSTRAINT `fk_rol_usuario` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`),
  CONSTRAINT `fk_usuario_colaborador` FOREIGN KEY (`id_colaborador`) REFERENCES `colaboradores` (`id_colaborador`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,1,1,'admin','$2y$10$/Dskysr7mdIrTKXyQAYim.7yhyHWGEw.pBOAL288j70oBBuJZ6nnS',NULL,'admin',NULL,0.00,'Inactivo','92b7f5349db1aa25b239e217eb0e5f8b71a5fd79e7d71be53ff7a090b95f0de3','2026-01-10 17:15:37','2026-01-09 17:18:03','2026-01-09 23:14:45','2026-01-09 23:18:03'),(2,2,2,'gerente','$2y$10$axgpHN4UMEINanKVMaWH6Od9qr4ugILohtj1ncdM3htXsuFFJ3mLi',NULL,'cliente',NULL,0.00,'Activo',NULL,NULL,'2026-01-11 10:55:03','2026-01-09 23:18:03','2026-01-11 16:55:06'),(3,3,3,'analista','$2y$10$/QNntQdcWFFEkXwlEm9Bn.FNThge5wjvRsnmJg6UfRvOGG1SK2kJO',NULL,'cliente',NULL,0.00,'Activo',NULL,NULL,'2026-01-11 12:08:50','2026-01-09 23:22:20','2026-01-11 18:08:51'),(4,4,5,'operaciones','$2y$10$VUaRxne1JlIBY1OLyDTHUuwyu3p71r3FDzpbswRbSbVx8Hust0Qau',NULL,'cliente',NULL,0.00,'Activo',NULL,NULL,'2026-01-11 13:03:22','2026-01-09 23:23:47','2026-01-11 19:03:29'),(5,5,4,'asesorcom1','$2y$10$/uARQGKVOsr.uKv6TUKdCetlmmb7RoLu3gfrIZO.M/jnVTnGFLTem',NULL,'cliente',NULL,0.00,'Activo',NULL,NULL,'2026-01-11 12:23:38','2026-01-09 23:27:23','2026-01-11 19:22:10'),(6,6,4,'desembolsadorcom1','$2y$10$sseYRD6y/lFRCLFvxnOIwuljirqGwrfqiROMM5z9//GMv90rbiSOG',NULL,'cliente',NULL,0.00,'Activo',NULL,NULL,'2026-01-11 11:08:54','2026-01-09 23:29:05','2026-01-11 17:09:00'),(7,7,4,'verificadorcom1','$2y$10$ZTk1wCbRLfs0n0HkbmTxPOjD2jUIXcENkkz0RcsuaxrDlBvmI6A4m',NULL,'cliente',NULL,0.00,'Activo',NULL,NULL,NULL,'2026-01-09 23:33:22','2026-01-09 23:33:22'),(8,8,5,'operacionesvalle','$2y$10$naLXnMLgBwImb.KBv2txwuwvXCdfG2yiQW8URxIrTMJ/7LfRn4M3K',NULL,'cliente',NULL,0.00,'Activo',NULL,NULL,'2026-01-11 12:43:03','2026-01-09 23:41:13','2026-01-11 18:43:04'),(9,9,4,'desembolsadorcom2','$2y$10$Mzdj6w3BvMUPF4eXBMXnu.lA504R7oxciYhG7/LxQnuoqUtqOPjFW',NULL,'cliente',NULL,0.00,'Activo',NULL,NULL,'2026-01-11 11:09:28','2026-01-09 23:43:39','2026-01-11 17:09:31'),(10,10,4,'asesorcom2','$2y$10$kyJ9cNCVQuW3ryHbZQ7VQu1OYTkVzLJFMVaq/2XhQL85esTcjd912',NULL,'cliente',NULL,0.00,'Activo','d7811678252912d3cd8f49eb563f19ac9a81387cecac19873288d72a55e80d3b','2026-01-12 12:47:00','2026-01-11 13:09:50','2026-01-09 23:45:09','2026-01-11 19:22:10'),(11,11,4,'asesorvalle1','$2y$10$wKqfreqA1mUdlpUU0mmDNuT4DOlIiCcmfDNwUuj2uonlOqaFhD/pW',NULL,'cliente',NULL,0.00,'Activo',NULL,NULL,'2026-01-11 12:46:51','2026-01-09 23:47:12','2026-01-11 19:22:10'),(12,12,4,'desembolsadorvalle1','$2y$10$w348Qt25xSOngFSS0QHlV.IyoFpocx5qLB9HRpI4cY0nhuYXE0PMK',NULL,'cliente',NULL,0.00,'Activo',NULL,NULL,'2026-01-11 11:12:44','2026-01-09 23:48:34','2026-01-11 18:06:15'),(13,13,3,'supagenvalle','$2y$10$8MvZ3p5NTfEJFrmYWA4GkuvzguMUJ7ukYiNA0YqcOzR.0Yu10lIYa',NULL,'cliente',NULL,0.00,'Activo',NULL,NULL,'2026-01-10 10:36:15','2026-01-09 23:50:17','2026-01-10 16:36:19'),(14,14,4,'verificadorvalle1','$2y$10$4GwGK5xGa1.39iFl9eV1DOENDxW4uj8bKOYWxWufSwRWRLSu65NpO',NULL,'cliente',NULL,0.00,'Activo',NULL,NULL,NULL,'2026-01-09 23:51:45','2026-01-09 23:51:45'),(15,15,4,'promotorcom1','$2y$10$tIwXpaO8qT6pN43XejS4n.J7apbb2Of6GhyNfJvtuGEGNjmAdw.Lq',NULL,'cliente',NULL,0.00,'Activo',NULL,NULL,'2026-01-10 12:45:03','2026-01-10 00:51:31','2026-01-10 18:45:07'),(16,16,3,'supagensantabarbara','$2y$10$zl5fqltyOzncfMaFm.Rt9e/JB8k207mKECn9HiCdygLDP5Q3AiPFK',NULL,'cliente',NULL,0.00,'Activo',NULL,NULL,NULL,'2026-01-10 16:41:43','2026-01-10 16:41:43'),(17,17,5,'operacionessantabarbara','$2y$10$dmJgVSTMAVwVFKzfRSlHT.ZMNezx6flQNPOri6arizqntqCxODA.m',NULL,'cliente',NULL,0.00,'Activo','2f78d0af56402f353d1afbba07e0052ec732e707108b17568531f6b5f74cc30c','2026-01-12 13:03:33','2026-01-11 14:23:37','2026-01-10 16:43:17','2026-01-11 20:23:37'),(18,18,4,'asesorsantabarbara','$2y$10$9zugD/gne0lZy.pudnxcCOAD5cY4XFu0q5JzX1nyFG2XQDl8Jb5ae',NULL,'cliente',NULL,0.00,'Activo',NULL,NULL,'2026-01-11 12:27:53','2026-01-10 16:44:34','2026-01-11 19:22:10'),(19,19,4,'desembolsadorsantabarbara1','$2y$10$.l3EmscCO98sPBs0VsUGw.6S7rTJB1upV125iLr89VAiG53Up.ohG',NULL,'cliente',NULL,0.00,'Activo',NULL,NULL,'2026-01-11 12:10:44','2026-01-10 17:07:41','2026-01-11 18:10:45');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `v_cuotas_pendientes`
--

DROP TABLE IF EXISTS `v_cuotas_pendientes`;

-- failed on view `v_cuotas_pendientes`: CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_cuotas_pendientes` AS select `cu`.`id` AS `id`,`cu`.`prestamo_id` AS `prestamo_id`,`cu`.`numero_cuota` AS `numero_cuota`,`cu`.`monto_cuota` AS `monto_cuota`,`cu`.`fecha_vencimiento` AS `fecha_vencimiento`,`cu`.`estado` AS `estado`,`cu`.`dias_mora` AS `dias_mora`,`cu`.`monto_mora` AS `monto_mora`,`p`.`numero_prestamo` AS `numero_prestamo`,`c`.`id` AS `cliente_id`,`c`.`nombre_completo` AS `cliente_nombre`,`c`.`codigo_cliente` AS `codigo_cliente`,`c`.`telefono` AS `telefono`,`u`.`id` AS `cobrador_id`,`u`.`nombre_completo` AS `cobrador_nombre` from (((`cuotas` `cu` join `prestamos` `p` on((`cu`.`prestamo_id` = `p`.`id`))) join `clientes` `c` on((`p`.`cliente_id` = `c`.`id`))) left join `usuarios` `u` on((`c`.`cobrador_id` = `u`.`id`))) where (`cu`.`estado` in ('pendiente','en_mora')) order by `cu`.`fecha_vencimiento`


--
-- Temporary view structure for view `v_prestamos_resumen`
--

DROP TABLE IF EXISTS `v_prestamos_resumen`;

-- failed on view `v_prestamos_resumen`: CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_prestamos_resumen` AS select `p`.`id` AS `id`,`p`.`numero_prestamo` AS `numero_prestamo`,`p`.`cliente_id` AS `cliente_id`,`c`.`nombre_completo` AS `cliente_nombre`,`c`.`codigo_cliente` AS `codigo_cliente`,`p`.`monto_prestado` AS `monto_prestado`,`p`.`monto_total` AS `monto_total`,`p`.`estado` AS `estado`,count(`cu`.`id`) AS `total_cuotas`,sum((case when (`cu`.`estado` = 'pagada') then 1 else 0 end)) AS `cuotas_pagadas`,sum((case when (`cu`.`estado` = 'pendiente') then 1 else 0 end)) AS `cuotas_pendientes`,sum((case when (`cu`.`estado` = 'en_mora') then 1 else 0 end)) AS `cuotas_en_mora`,sum(`cu`.`monto_pagado`) AS `monto_pagado_total`,(`p`.`monto_total` - coalesce(sum(`cu`.`monto_pagado`),0)) AS `saldo_pendiente` from ((`prestamos` `p` join `clientes` `c` on((`p`.`cliente_id` = `c`.`id`))) left join `cuotas` `cu` on((`p`.`id` = `cu`.`prestamo_id`))) group by `p`.`id`,`p`.`numero_prestamo`,`p`.`cliente_id`,`c`.`nombre_completo`,`c`.`codigo_cliente`,`p`.`monto_prestado`,`p`.`monto_total`,`p`.`estado`


--
-- Dumping routines for database 'sistema_financiera'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-11 14:26:41
