-- MySQL dump 10.13  Distrib 9.7.1, for macos26.4 (arm64)
--
-- Host: localhost    Database: mesa_digital
-- ------------------------------------------------------
-- Server version	9.7.1

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
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
SET @@SESSION.SQL_LOG_BIN= 0;

--
-- GTID state at the beginning of the backup 
--

SET @@GLOBAL.GTID_PURGED=/*!80000 '+'*/ 'e8ed7be4-7a6f-11f1-921f-33231606dd44:1-133928';

--
-- Table structure for table `caracteres_remitente`
--

DROP TABLE IF EXISTS `caracteres_remitente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `caracteres_remitente` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orden` int DEFAULT '0',
  `activo` tinyint(1) DEFAULT '1',
  `creado_el` datetime DEFAULT CURRENT_TIMESTAMP,
  `creado_por` int unsigned DEFAULT NULL,
  `modificado_el` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `modificado_por` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_caracteres_remitente_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caracteres_remitente`
--

LOCK TABLES `caracteres_remitente` WRITE;
/*!40000 ALTER TABLE `caracteres_remitente` DISABLE KEYS */;
INSERT INTO `caracteres_remitente` VALUES (1,'Empleado',1,1,'2026-08-31 13:42:04',NULL,NULL,NULL),(2,'Familia',2,1,'2026-08-31 13:42:04',NULL,NULL,NULL),(3,'Sindicato',3,1,'2026-08-31 13:42:04',NULL,NULL,NULL),(4,'Municipalidad/Organismo público',4,1,'2026-08-31 13:42:04',NULL,NULL,NULL),(5,'Juzgado',5,1,'2026-08-31 13:42:04',NULL,NULL,NULL),(6,'Proveedor',6,1,'2026-08-31 13:42:04',NULL,NULL,NULL),(7,'Otro',7,1,'2026-08-31 13:42:04',NULL,NULL,NULL),(8,'Car Test',5,1,'2026-09-01 09:35:34',1,NULL,NULL);
/*!40000 ALTER TABLE `caracteres_remitente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categoria_responsables`
--

DROP TABLE IF EXISTS `categoria_responsables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categoria_responsables` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `categoria_id` int unsigned DEFAULT NULL,
  `sede_id` int unsigned DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_id` int unsigned DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `creado_el` datetime DEFAULT CURRENT_TIMESTAMP,
  `creado_por` int unsigned DEFAULT NULL,
  `modificado_el` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `modificado_por` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categoria_email` (`categoria_id`,`email`),
  KEY `idx_categoria_responsables_usuario` (`usuario_id`),
  KEY `idx_categoria_responsables_sede` (`sede_id`),
  CONSTRAINT `categoria_responsables_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `categoria_responsables_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_categoria_responsables_sede` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categoria_responsables`
--

LOCK TABLES `categoria_responsables` WRITE;
/*!40000 ALTER TABLE `categoria_responsables` DISABLE KEYS */;
INSERT INTO `categoria_responsables` VALUES (1,1,NULL,'legales@reditinere.com',12,1,'2026-08-31 13:42:05',NULL,NULL,NULL),(2,1,NULL,'estudio.juridico.externo@legalcorp.com.ar',NULL,1,'2026-08-31 13:42:05',NULL,NULL,NULL),(3,2,NULL,'legales@reditinere.com',12,1,'2026-08-31 13:42:05',NULL,NULL,NULL),(4,3,NULL,'legales@reditinere.com',12,1,'2026-08-31 13:42:05',NULL,NULL,NULL),(5,4,NULL,'rrhh@reditinere.com',13,1,'2026-08-31 13:42:05',NULL,NULL,NULL),(6,5,NULL,'mantenimiento@reditinere.com',14,1,'2026-08-31 13:42:05',NULL,NULL,NULL),(7,6,NULL,'admin@reditinere.com',1,1,'2026-08-31 13:42:05',NULL,NULL,NULL),(8,7,NULL,'administracion@reditinere.com',15,1,'2026-08-31 13:42:05',NULL,NULL,NULL),(9,8,NULL,'mantenimiento@reditinere.com',14,1,'2026-08-31 13:42:05',NULL,NULL,NULL),(10,9,NULL,'sistemas@reditinere.com',16,1,'2026-08-31 13:42:05',NULL,NULL,NULL),(11,10,NULL,'rrhh@reditinere.com',13,1,'2026-08-31 13:42:05',NULL,NULL,NULL),(12,11,NULL,'admin@reditinere.com',1,1,'2026-08-31 13:42:05',NULL,NULL,NULL),(13,1,NULL,'externo@abogados.com',NULL,0,'2026-09-01 09:35:54',1,'2026-09-01 09:35:54',NULL);
/*!40000 ALTER TABLE `categoria_responsables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orden` int DEFAULT '0',
  `es_reserva` tinyint(1) DEFAULT '0',
  `activo` tinyint(1) DEFAULT '1',
  `creado_el` datetime DEFAULT CURRENT_TIMESTAMP,
  `creado_por` int unsigned DEFAULT NULL,
  `modificado_el` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `modificado_por` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categorias_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES (1,'Legales Modificado','Desc mod',1,0,1,'2026-08-31 13:42:04',NULL,'2026-09-01 09:35:54',1),(2,'Legales — Civil y Comercial','Contratos, reclamos y cédulas judiciales',2,0,1,'2026-08-31 13:42:04',NULL,NULL,NULL),(3,'Legales — Municipal / Regulatorio','Inspecciones, tasas y requerimientos de organismos',3,0,1,'2026-08-31 13:42:04',NULL,NULL,NULL),(4,'Capital Humano (RRHH)','Comunicaciones de personal, licencias y legajos',4,0,1,'2026-08-31 13:42:04',NULL,NULL,NULL),(5,'Real Estate / Mantenimiento Edilicio','Obras, proveedores de infraestructura y servicios',5,0,1,'2026-08-31 13:42:04',NULL,NULL,NULL),(6,'Dirección Académica','Notas de familias, proyectos pedagógicos y circulares',6,0,1,'2026-08-31 13:42:04',NULL,NULL,NULL),(7,'Administración y Facturación','Facturas, comprobantes de pago y cobranzas',7,0,1,'2026-08-31 13:42:04',NULL,NULL,NULL),(8,'Seguridad e Higiene','Protocolos de evacuación, simulacros y revisiones ART',8,0,1,'2026-08-31 13:42:04',NULL,NULL,NULL),(9,'Sistemas / IT','Equipamiento informático, licencias y conectividad',9,0,1,'2026-08-31 13:42:04',NULL,NULL,NULL),(10,'Paquetería Personal','Envíos personales para colaboradores de la institución',10,0,1,'2026-08-31 13:42:04',NULL,NULL,NULL),(11,'Sin Clasificar','Categoría de reserva para reasignación y descarte',99,1,1,'2026-08-31 13:42:04',NULL,NULL,NULL),(12,'Cat Test','Desc',10,0,1,'2026-09-01 09:35:34',1,NULL,NULL);
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documento_adjuntos`
--

DROP TABLE IF EXISTS `documento_adjuntos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documento_adjuntos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `documento_id` int unsigned DEFAULT NULL,
  `nombre_original` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ruta_almacenamiento` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tamano_bytes` int unsigned DEFAULT NULL,
  `es_principal` tinyint(1) DEFAULT '0',
  `subido_por` int unsigned DEFAULT NULL,
  `creado_el` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_adjuntos_documento` (`documento_id`),
  KEY `subido_por` (`subido_por`),
  CONSTRAINT `documento_adjuntos_ibfk_1` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documento_adjuntos_ibfk_2` FOREIGN KEY (`subido_por`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documento_adjuntos`
--

LOCK TABLES `documento_adjuntos` WRITE;
/*!40000 ALTER TABLE `documento_adjuntos` DISABLE KEYS */;
INSERT INTO `documento_adjuntos` VALUES (1,1,'telegrama_laboral.jpg','storage/documentos/seed_placeholder.jpg','image/jpeg',102400,1,2,'2026-08-31 15:42:05'),(2,2,'carta_documento.pdf','storage/documentos/seed_placeholder.pdf','application/pdf',204800,1,3,'2026-08-29 16:42:05'),(3,3,'certificado_inspeccion.jpg','storage/documentos/seed_placeholder.jpg','image/jpeg',150000,1,4,'2026-08-26 16:42:05'),(4,4,'remito_factura.jpg','storage/documentos/seed_placeholder.jpg','image/jpeg',120000,1,5,'2026-08-16 16:42:05'),(5,5,'Screenshot 2026-08-31 at 10.23.34 AM.png','documentos/2026/08/1/5/ae1480303abfb6ce_1788199265.png','image/png',449540,1,2,'2026-08-31 15:01:05'),(6,6,'test.pdf','documentos/2026/09/1/6/24cd17099baa9260_1788266134.pdf','application/pdf',13,1,1,'2026-09-01 09:35:34');
/*!40000 ALTER TABLE `documento_adjuntos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documento_comentarios`
--

DROP TABLE IF EXISTS `documento_comentarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documento_comentarios` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `documento_id` int unsigned DEFAULT NULL,
  `usuario_id` int unsigned DEFAULT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci,
  `creado_el` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comentarios_documento` (`documento_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `documento_comentarios_ibfk_1` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documento_comentarios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documento_comentarios`
--

LOCK TABLES `documento_comentarios` WRITE;
/*!40000 ALTER TABLE `documento_comentarios` DISABLE KEYS */;
INSERT INTO `documento_comentarios` VALUES (1,5,1,'cvbnm','2026-08-31 15:53:55'),(2,1,1,'Comentario de prueba','2026-09-01 09:35:54');
/*!40000 ALTER TABLE `documento_comentarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documento_historial`
--

DROP TABLE IF EXISTS `documento_historial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documento_historial` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `documento_id` int unsigned DEFAULT NULL,
  `fecha_hora` datetime DEFAULT CURRENT_TIMESTAMP,
  `accion` enum('Cargado','Notificacion_enviada','Notificacion_fallida','Marcado_en_curso','Reclasificado','Comentario','Adjunto_agregado','Plazo_legal_modificado','Marcado_resuelto','Cerrado','Anulado') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_anterior` enum('Recibido','En curso','Resuelto','Cerrado') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_nuevo` enum('Recibido','En curso','Resuelto','Cerrado') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_id` int unsigned DEFAULT NULL,
  `detalle` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_historial_documento` (`documento_id`),
  KEY `idx_historial_fecha` (`fecha_hora`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `documento_historial_ibfk_1` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documento_historial_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documento_historial`
--

LOCK TABLES `documento_historial` WRITE;
/*!40000 ALTER TABLE `documento_historial` DISABLE KEYS */;
INSERT INTO `documento_historial` VALUES (1,1,'2026-08-31 15:42:05','Cargado',NULL,'Recibido',2,'Documento ingresado en Mesa Digital con 1 adjunto.'),(2,1,'2026-08-31 15:43:05','Notificacion_enviada',NULL,NULL,NULL,'Notificación enviada por email a los responsables de Legales — Laboral.'),(3,2,'2026-08-29 16:42:05','Cargado',NULL,'Recibido',3,'Documento ingresado en Mesa Digital.'),(4,2,'2026-08-30 16:42:05','Marcado_en_curso','Recibido','En curso',12,'Documento tomado para gestión legal.'),(5,3,'2026-08-26 16:42:05','Cargado',NULL,'Recibido',4,'Documento ingresado en Mesa Digital.'),(6,3,'2026-08-27 16:42:05','Marcado_en_curso','Recibido','En curso',14,'Tomado por responsable de mantenimiento.'),(7,3,'2026-08-28 16:42:05','Marcado_resuelto','En curso','Resuelto',14,'Constancia: Se recepcionó el certificado de mantenimiento y se archivó copia en la carpeta de infraestructura.'),(8,4,'2026-08-16 16:42:05','Cargado',NULL,'Recibido',5,'Documento ingresado en Mesa Digital.'),(9,4,'2026-08-17 16:42:05','Marcado_en_curso','Recibido','En curso',15,'Tomado para gestión contable.'),(10,4,'2026-08-18 16:42:05','Marcado_resuelto','En curso','Resuelto',15,'Constancia: Materiales entregados al departamento de arte y factura derivada a contabilidad.'),(11,4,'2026-08-21 16:42:05','Cerrado','Resuelto','Cerrado',1,'Archivo definitivo del documento.'),(12,5,'2026-08-31 15:01:05','Cargado',NULL,'Recibido',2,'Documento registrado en Mesa Digital con 1 adjunto(s).'),(13,5,'2026-08-31 15:53:55','Comentario','Recibido','Recibido',1,'Comentario agregado: \"cvbnm\"'),(14,6,'2026-09-01 09:35:34','Cargado',NULL,'Recibido',1,'Documento registrado en Mesa Digital con 1 adjunto(s).'),(15,1,'2026-09-01 09:35:54','Comentario','Recibido','Recibido',1,'Comentario agregado: \"Comentario de prueba\"'),(16,1,'2026-09-01 09:35:54','Reclasificado','Recibido','Recibido',1,'Reclasificado de \'Legales — Laboral\' a \'Legales — Civil y Comercial\'. Motivo: Motivo de reclasificacion valido'),(17,1,'2026-09-01 09:35:54','Plazo_legal_modificado','Recibido','Recibido',1,'Plazo legal actualizado a: 2026-10-15'),(18,1,'2026-09-01 09:35:54','Marcado_en_curso','Recibido','En curso',1,'El documento fue tomado para su gestión por Nicolás Administrador.'),(19,1,'2026-09-01 09:35:54','Marcado_resuelto','En curso','Resuelto',1,'Documento marcado como resuelto. Constancia: Constancia de cierre valida'),(20,1,'2026-09-01 09:35:54','Cerrado','Resuelto','Cerrado',1,'Archivado manualmente por Nicolás Administrador. (Archivo definitivo)'),(21,6,'2026-09-01 14:07:35','Marcado_en_curso','Recibido','En curso',1,'El documento fue tomado para su gestión por Nicolás Administrador.'),(22,6,'2026-09-01 14:07:58','Marcado_resuelto','En curso','Resuelto',1,'Documento marcado como resuelto. Constancia: asdasdasdasd');
/*!40000 ALTER TABLE `documento_historial` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documentos`
--

DROP TABLE IF EXISTS `documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documentos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sede_id` int unsigned DEFAULT NULL,
  `categoria_id` int unsigned DEFAULT NULL,
  `tipo_documento_id` int unsigned DEFAULT NULL,
  `caracter_remitente_id` int unsigned DEFAULT NULL,
  `remitente` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asunto` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `fecha_recepcion` date DEFAULT NULL,
  `plazo_legal` date DEFAULT NULL,
  `estado` enum('Recibido','En curso','Resuelto','Cerrado') COLLATE utf8mb4_unicode_ci DEFAULT 'Recibido',
  `constancia_cierre` text COLLATE utf8mb4_unicode_ci,
  `creado_por` int unsigned DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `motivo_anulacion` text COLLATE utf8mb4_unicode_ci,
  `creado_el` datetime DEFAULT CURRENT_TIMESTAMP,
  `modificado_el` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `modificado_por` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_documentos_codigo` (`codigo`),
  KEY `idx_documentos_sede` (`sede_id`),
  KEY `idx_documentos_categoria` (`categoria_id`),
  KEY `idx_documentos_estado` (`estado`),
  KEY `idx_documentos_fecha_recepcion` (`fecha_recepcion`),
  KEY `idx_documentos_plazo_legal` (`plazo_legal`),
  KEY `idx_documentos_remitente` (`remitente`),
  KEY `tipo_documento_id` (`tipo_documento_id`),
  KEY `caracter_remitente_id` (`caracter_remitente_id`),
  KEY `creado_por` (`creado_por`),
  CONSTRAINT `documentos_ibfk_1` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `documentos_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `documentos_ibfk_3` FOREIGN KEY (`tipo_documento_id`) REFERENCES `tipos_documento` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `documentos_ibfk_4` FOREIGN KEY (`caracter_remitente_id`) REFERENCES `caracteres_remitente` (`id`) ON DELETE SET NULL,
  CONSTRAINT `documentos_ibfk_5` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documentos`
--

LOCK TABLES `documentos` WRITE;
/*!40000 ALTER TABLE `documentos` DISABLE KEYS */;
INSERT INTO `documentos` VALUES (1,'MD-2026-000001',1,2,3,1,'Juan Carlos Pérez (Docente)','Telegrama laboral - Reclamo de haberes','Se recibe telegrama laboral intimando aclaración de situación registral.','2026-08-31','2026-10-15','Cerrado','Constancia de cierre valida',2,1,NULL,'2026-08-31 15:42:05','2026-09-01 09:35:54',1),(2,'MD-2026-000002',2,2,2,2,'Estudio Jurídico Gómez & Asoc.','Carta documento - Solicitud de información contractual','Requerimiento sobre aranceles ciclo lectivo 2026.','2026-08-29','2026-09-01','En curso',NULL,3,1,NULL,'2026-08-29 16:42:05',NULL,NULL),(3,'MD-2026-000003',3,5,6,6,'Ascensores del Norte S.A.','Certificado de mantenimiento trimestral','Entrega de certificado y protocolo de inspección técnica obligatoria.','2026-08-26',NULL,'Resuelto','Se recepcionó el certificado de mantenimiento y se archivó copia en la carpeta de infraestructura.',4,1,NULL,'2026-08-26 16:42:05',NULL,NULL),(4,'MD-2026-000004',4,7,5,6,'Librería Escolar Central','Factura y remito de materiales de arte','Paquete con insumos para el taller de plástica del nivel primario.','2026-08-16',NULL,'Cerrado','Materiales entregados al departamento de arte y factura derivada a contabilidad.',5,1,NULL,'2026-08-16 16:42:05',NULL,NULL),(5,'MD-2026-000005',1,7,3,6,'asda','sdfs','asdasdpdf','2026-08-31','2026-09-25','Recibido',NULL,2,1,NULL,'2026-08-31 15:01:05',NULL,NULL),(6,'MD-2026-000006',1,1,1,NULL,'Remitente Test','Asunto Test','Descripcion Test','2026-09-01',NULL,'Resuelto','asdasdasdasd',1,1,NULL,'2026-09-01 09:35:34','2026-09-01 14:07:58',1);
/*!40000 ALTER TABLE `documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_intentos`
--

DROP TABLE IF EXISTS `login_intentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_intentos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `intentado_el` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_ip_email` (`ip_address`,`email`,`intentado_el`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_intentos`
--

LOCK TABLES `login_intentos` WRITE;
/*!40000 ALTER TABLE `login_intentos` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_intentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificaciones_enviadas`
--

DROP TABLE IF EXISTS `notificaciones_enviadas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificaciones_enviadas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `documento_id` int unsigned DEFAULT NULL,
  `destinatario_email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_evento` enum('Documento_cargado','Recordatorio_48h','Alerta_vencimiento_3d','Alerta_vencimiento_1d','Documento_reclasificado','Documento_resuelto','Sin_responsable') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_envio` enum('Pendiente','Enviado','Fallido') COLLATE utf8mb4_unicode_ci DEFAULT 'Pendiente',
  `intento_numero` tinyint unsigned DEFAULT '1',
  `fecha_envio` datetime DEFAULT NULL,
  `error_mensaje` text COLLATE utf8mb4_unicode_ci,
  `creado_el` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_documento` (`documento_id`),
  KEY `idx_notif_estado` (`estado_envio`),
  CONSTRAINT `notificaciones_enviadas_ibfk_1` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificaciones_enviadas`
--

LOCK TABLES `notificaciones_enviadas` WRITE;
/*!40000 ALTER TABLE `notificaciones_enviadas` DISABLE KEYS */;
INSERT INTO `notificaciones_enviadas` VALUES (1,5,'administracion@reditinere.com','Documento_cargado','Pendiente',1,NULL,NULL,'2026-08-31 15:01:06'),(2,6,'estudio.juridico.externo@legalcorp.com.ar','Documento_cargado','Pendiente',1,NULL,NULL,'2026-09-01 09:35:34'),(3,6,'legales@reditinere.com','Documento_cargado','Pendiente',1,NULL,NULL,'2026-09-01 09:35:34'),(4,1,'legales@reditinere.com','Documento_reclasificado','Pendiente',1,NULL,NULL,'2026-09-01 09:35:54'),(5,1,'recepcion.benavidez@reditinere.com','Documento_resuelto','Pendiente',1,NULL,NULL,'2026-09-01 09:35:54'),(6,6,'admin@reditinere.com','Documento_resuelto','Pendiente',1,NULL,NULL,'2026-09-01 14:07:58');
/*!40000 ALTER TABLE `notificaciones_enviadas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phinxlog`
--

DROP TABLE IF EXISTS `phinxlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `phinxlog` (
  `version` bigint NOT NULL,
  `migration_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `breakpoint` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phinxlog`
--

LOCK TABLES `phinxlog` WRITE;
/*!40000 ALTER TABLE `phinxlog` DISABLE KEYS */;
INSERT INTO `phinxlog` VALUES (20260901000001,'CreateSedesTable','2026-08-31 19:41:50','2026-08-31 19:41:50',0),(20260901000002,'CreateCategoriasTable','2026-08-31 19:41:50','2026-08-31 19:41:50',0),(20260901000003,'CreateTiposDocumentoTable','2026-08-31 19:41:50','2026-08-31 19:41:50',0),(20260901000004,'CreateCaracteresRemitenteTable','2026-08-31 19:41:50','2026-08-31 19:41:50',0),(20260901000005,'CreateUsuariosTable','2026-08-31 19:41:50','2026-08-31 19:41:50',0),(20260901000006,'CreateCategoriaResponsablesTable','2026-08-31 19:41:50','2026-08-31 19:41:50',0),(20260901000007,'CreateDocumentosTable','2026-08-31 19:41:50','2026-08-31 19:41:50',0),(20260901000008,'CreateDocumentoAdjuntosTable','2026-08-31 19:41:50','2026-08-31 19:41:50',0),(20260901000009,'CreateDocumentoHistorialTable','2026-08-31 19:41:50','2026-08-31 19:41:50',0),(20260901000010,'CreateDocumentoComentariosTable','2026-08-31 19:41:50','2026-08-31 19:41:50',0),(20260901000011,'CreateNotificacionesEnviadasTable','2026-08-31 19:41:50','2026-08-31 19:41:50',0),(20260901000012,'CreateLoginIntentosTable','2026-08-31 19:41:50','2026-08-31 19:41:50',0);
/*!40000 ALTER TABLE `phinxlog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sedes`
--

DROP TABLE IF EXISTS `sedes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sedes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_primario` char(7) COLLATE utf8mb4_unicode_ci DEFAULT '#4E47DD',
  `activo` tinyint(1) DEFAULT '1',
  `creado_el` datetime DEFAULT CURRENT_TIMESTAMP,
  `creado_por` int unsigned DEFAULT NULL,
  `modificado_el` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `modificado_por` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sedes_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sedes`
--

LOCK TABLES `sedes` WRITE;
/*!40000 ALTER TABLE `sedes` DISABLE KEYS */;
INSERT INTO `sedes` VALUES (1,'Colegio del Faro Benavidez','#295E48',1,'2026-08-31 13:42:04',NULL,NULL,NULL),(2,'Colegio del Faro Escobar','#3E7D61',1,'2026-08-31 13:42:04',NULL,NULL,NULL),(3,'Lighthouse Campus Puertos','#3A82C2',1,'2026-08-31 13:42:04',NULL,NULL,NULL),(4,'Northfield Nordelta','#CF364C',1,'2026-08-31 13:42:04',NULL,NULL,NULL),(5,'Northfield Puertos','#4E47DD',1,'2026-08-31 13:42:04',NULL,NULL,NULL),(6,'Sede Test','#123456',1,'2026-09-01 09:35:34',1,NULL,NULL);
/*!40000 ALTER TABLE `sedes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_documento`
--

DROP TABLE IF EXISTS `tipos_documento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_documento` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `categoria_id` int unsigned DEFAULT NULL,
  `orden` int DEFAULT '0',
  `activo` tinyint(1) DEFAULT '1',
  `creado_el` datetime DEFAULT CURRENT_TIMESTAMP,
  `creado_por` int unsigned DEFAULT NULL,
  `modificado_el` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `modificado_por` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tipos_documento_nombre` (`nombre`),
  KEY `fk_tipos_documento_categoria` (`categoria_id`),
  CONSTRAINT `fk_tipos_documento_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipos_documento`
--

LOCK TABLES `tipos_documento` WRITE;
/*!40000 ALTER TABLE `tipos_documento` DISABLE KEYS */;
INSERT INTO `tipos_documento` VALUES (1,'Oficio',1,1,1,'2026-08-31 13:42:04',NULL,'2026-09-01 13:56:29',NULL),(2,'Carta documento',1,2,1,'2026-08-31 13:42:04',NULL,'2026-09-01 13:56:29',NULL),(3,'Telegrama',4,3,1,'2026-08-31 13:42:04',NULL,'2026-09-01 13:56:29',NULL),(4,'Cédula de notificación',1,4,1,'2026-08-31 13:42:04',NULL,'2026-09-01 13:56:29',NULL),(5,'Paquete/Encomienda',10,5,1,'2026-08-31 13:42:04',NULL,'2026-09-01 13:56:29',NULL),(6,'Nota de reclamo o pedido',6,6,1,'2026-08-31 13:42:04',NULL,'2026-09-01 13:56:29',NULL),(7,'Otro',11,7,1,'2026-08-31 13:42:04',NULL,'2026-09-01 13:56:29',NULL),(8,'Tipo Test',NULL,5,1,'2026-09-01 09:35:34',1,NULL,NULL);
/*!40000 ALTER TABLE `tipos_documento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rol` enum('administrador','recepcion_sede','responsable_categoria','direccion_sede','supervision_general') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sede_id` int unsigned DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_sub` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `ultimo_login` datetime DEFAULT NULL,
  `creado_el` datetime DEFAULT CURRENT_TIMESTAMP,
  `creado_por` int unsigned DEFAULT NULL,
  `modificado_el` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `modificado_por` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_email` (`email`),
  KEY `idx_usuarios_rol` (`rol`),
  KEY `sede_id` (`sede_id`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Nicolás Administrador','admin@reditinere.com','administrador',NULL,'$2y$12$95hJRWSbxuA5jPzW7TE1yueKlPVNd.vYPiELSFWm3r2wboj89pyAG',NULL,1,'2026-09-01 15:42:14','2026-08-31 13:42:05',NULL,'2026-09-01 15:42:14',NULL),(2,'Recepción Benavidez','recepcion.benavidez@reditinere.com','recepcion_sede',1,'$2y$12$95hJRWSbxuA5jPzW7TE1yueKlPVNd.vYPiELSFWm3r2wboj89pyAG',NULL,1,'2026-09-01 14:09:01','2026-08-31 13:42:05',NULL,'2026-09-01 14:09:01',NULL),(3,'Recepción Escobar','recepcion.escobar@reditinere.com','recepcion_sede',2,'$2y$12$95hJRWSbxuA5jPzW7TE1yueKlPVNd.vYPiELSFWm3r2wboj89pyAG',NULL,1,NULL,'2026-08-31 13:42:05',NULL,NULL,NULL),(4,'Recepción Lighthouse','recepcion.lighthouse@reditinere.com','recepcion_sede',3,'$2y$12$95hJRWSbxuA5jPzW7TE1yueKlPVNd.vYPiELSFWm3r2wboj89pyAG',NULL,1,NULL,'2026-08-31 13:42:05',NULL,NULL,NULL),(5,'Recepción Nordelta','recepcion.nordelta@reditinere.com','recepcion_sede',4,'$2y$12$95hJRWSbxuA5jPzW7TE1yueKlPVNd.vYPiELSFWm3r2wboj89pyAG',NULL,1,NULL,'2026-08-31 13:42:05',NULL,NULL,NULL),(6,'Recepción Puertos','recepcion.puertos@reditinere.com','recepcion_sede',5,'$2y$12$95hJRWSbxuA5jPzW7TE1yueKlPVNd.vYPiELSFWm3r2wboj89pyAG',NULL,1,NULL,'2026-08-31 13:42:05',NULL,NULL,NULL),(7,'Dirección Benavidez','direccion.benavidez@reditinere.com','direccion_sede',1,'$2y$12$95hJRWSbxuA5jPzW7TE1yueKlPVNd.vYPiELSFWm3r2wboj89pyAG',NULL,1,'2026-08-31 15:39:07','2026-08-31 13:42:05',NULL,'2026-08-31 15:39:07',NULL),(8,'Dirección Escobar','direccion.escobar@reditinere.com','direccion_sede',2,'$2y$12$95hJRWSbxuA5jPzW7TE1yueKlPVNd.vYPiELSFWm3r2wboj89pyAG',NULL,1,'2026-08-31 15:39:14','2026-08-31 13:42:05',NULL,'2026-08-31 15:39:14',NULL),(9,'Dirección Lighthouse','direccion.lighthouse@reditinere.com','direccion_sede',3,'$2y$12$95hJRWSbxuA5jPzW7TE1yueKlPVNd.vYPiELSFWm3r2wboj89pyAG',NULL,1,'2026-08-31 15:39:20','2026-08-31 13:42:05',NULL,'2026-08-31 15:39:20',NULL),(10,'Dirección Nordelta','direccion.nordelta@reditinere.com','direccion_sede',4,'$2y$12$95hJRWSbxuA5jPzW7TE1yueKlPVNd.vYPiELSFWm3r2wboj89pyAG',NULL,1,NULL,'2026-08-31 13:42:05',NULL,NULL,NULL),(11,'Dirección Puertos','direccion.puertos@reditinere.com','direccion_sede',5,'$2y$12$95hJRWSbxuA5jPzW7TE1yueKlPVNd.vYPiELSFWm3r2wboj89pyAG',NULL,1,NULL,'2026-08-31 13:42:05',NULL,NULL,NULL),(12,'Dra. Laura Legales','legales@reditinere.com','responsable_categoria',NULL,'$2y$12$95hJRWSbxuA5jPzW7TE1yueKlPVNd.vYPiELSFWm3r2wboj89pyAG',NULL,1,NULL,'2026-08-31 13:42:05',NULL,NULL,NULL),(13,'Martín Capital Humano','rrhh@reditinere.com','responsable_categoria',NULL,'$2y$12$95hJRWSbxuA5jPzW7TE1yueKlPVNd.vYPiELSFWm3r2wboj89pyAG',NULL,1,NULL,'2026-08-31 13:42:05',NULL,NULL,NULL),(14,'Esteban Mantenimiento','mantenimiento@reditinere.com','responsable_categoria',NULL,'$2y$12$95hJRWSbxuA5jPzW7TE1yueKlPVNd.vYPiELSFWm3r2wboj89pyAG',NULL,1,NULL,'2026-08-31 13:42:05',NULL,NULL,NULL),(15,'Carla Administración','administracion@reditinere.com','responsable_categoria',NULL,'$2y$12$95hJRWSbxuA5jPzW7TE1yueKlPVNd.vYPiELSFWm3r2wboj89pyAG',NULL,1,'2026-09-01 10:00:22','2026-08-31 13:42:05',NULL,'2026-09-01 10:00:22',NULL),(16,'Tomás Sistemas','sistemas@reditinere.com','responsable_categoria',NULL,'$2y$12$95hJRWSbxuA5jPzW7TE1yueKlPVNd.vYPiELSFWm3r2wboj89pyAG',NULL,1,NULL,'2026-08-31 13:42:05',NULL,NULL,NULL),(17,'Sofía Supervisora','supervision@reditinere.com','supervision_general',NULL,'$2y$12$95hJRWSbxuA5jPzW7TE1yueKlPVNd.vYPiELSFWm3r2wboj89pyAG',NULL,1,NULL,'2026-08-31 13:42:05',NULL,NULL,NULL),(18,'Test User','testuser@reditinere.com','recepcion_sede',1,'$2y$12$o9A07k7k0zkBoISeO5a5vOqQ1ppvPF4eU7iu5ZhfqmnDHji7HSZsO',NULL,1,NULL,'2026-09-01 09:35:34',1,NULL,NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-01 15:51:50
