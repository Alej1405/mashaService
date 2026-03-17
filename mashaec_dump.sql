/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.2.2-MariaDB, for osx10.19 (x86_64)
--
-- Host: localhost    Database: erpMasha
-- ------------------------------------------------------
-- Server version	12.2.2-MariaDB

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
-- Table structure for table `account_plans`
--

DROP TABLE IF EXISTS `account_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('activo','pasivo','patrimonio','ingreso','costo','gasto') NOT NULL,
  `nature` enum('deudora','acreedora') NOT NULL,
  `parent_code` varchar(255) DEFAULT NULL,
  `level` int(11) NOT NULL,
  `accepts_movements` tinyint(1) NOT NULL DEFAULT 0,
  `modulo` enum('base','logistica','comercio_exterior','productos','servicios','manufactura') DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_plans_empresa_id_code_unique` (`empresa_id`,`code`),
  CONSTRAINT `account_plans_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=302 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_plans`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `account_plans` WRITE;
/*!40000 ALTER TABLE `account_plans` DISABLE KEYS */;
INSERT INTO `account_plans` VALUES
(1,NULL,'1','ACTIVO','activo','deudora',NULL,1,0,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(2,NULL,'2','PASIVO','pasivo','acreedora',NULL,1,0,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(3,NULL,'3','PATRIMONIO NETO','patrimonio','acreedora',NULL,1,0,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(4,NULL,'4','INGRESOS','ingreso','acreedora',NULL,1,0,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(5,NULL,'5','COSTOS','costo','deudora',NULL,1,0,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(6,NULL,'6','GASTOS','gasto','deudora',NULL,1,0,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(7,NULL,'1.1','Activo Corriente','activo','deudora','1',2,0,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(8,NULL,'1.1.01','Efectivo y equivalentes del efectivo','activo','deudora','1.1',3,0,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(9,NULL,'1.1.01.01','Caja general','activo','deudora','1.1.01',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(10,NULL,'1.1.01.02','Caja chica','activo','deudora','1.1.01',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(11,NULL,'1.1.01.03','Bancos cuenta corriente','activo','deudora','1.1.01',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(12,NULL,'1.1.01.04','Bancos cuenta de ahorros','activo','deudora','1.1.01',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(13,NULL,'1.1.02','Activos financieros','activo','deudora','1.1',3,0,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(14,NULL,'1.1.02.01','Cuentas por cobrar clientes','activo','deudora','1.1.02',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(15,NULL,'1.1.02.02','Documentos por cobrar','activo','deudora','1.1.02',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(16,NULL,'1.1.02.03','(-) Provisión cuentas incobrables','activo','acreedora','1.1.02',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(17,NULL,'1.1.04','Servicios y otros pagos anticipados','activo','deudora','1.1',3,0,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(18,NULL,'1.1.04.01','Seguros pagados por anticipado','activo','deudora','1.1.04',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(19,NULL,'1.1.04.02','Arriendos pagados por anticipado','activo','deudora','1.1.04',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(20,NULL,'1.1.04.03','Anticipos a proveedores','activo','deudora','1.1.04',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(21,NULL,'1.1.05','Activos por impuestos corrientes','activo','deudora','1.1',3,0,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(22,NULL,'1.1.05.01','Crédito tributario IVA','activo','deudora','1.1.05',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(23,NULL,'1.1.05.02','Crédito tributario impuesto a la renta','activo','deudora','1.1.05',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(24,NULL,'1.1.05.03','Anticipo impuesto a la renta','activo','deudora','1.1.05',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(25,NULL,'1.2','Activo No Corriente','activo','deudora','1',2,0,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(26,NULL,'1.2.01','Propiedades, planta y equipo','activo','deudora','1.2',3,0,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(27,NULL,'1.2.01.01','Terrenos','activo','deudora','1.2.01',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(28,NULL,'1.2.01.02','Edificios y construcciones','activo','deudora','1.2.01',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(29,NULL,'1.2.01.03','Muebles y enseres','activo','deudora','1.2.01',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(30,NULL,'1.2.01.04','Maquinaria y equipo','activo','deudora','1.2.01',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(31,NULL,'1.2.01.05','Equipo de computación','activo','deudora','1.2.01',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(32,NULL,'1.2.01.06','Vehículos','activo','deudora','1.2.01',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(33,NULL,'1.2.01.07','(-) Depreciación acumulada PPE','activo','acreedora','1.2.01',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(34,NULL,'1.2.04','Activo intangible','activo','deudora','1.2',3,0,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(35,NULL,'1.2.04.01','Marcas y patentes','activo','deudora','1.2.04',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(36,NULL,'1.2.04.02','Programas de computación','activo','deudora','1.2.04',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(37,NULL,'1.2.04.03','(-) Amortización acumulada intangibles','activo','acreedora','1.2.04',4,1,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(38,NULL,'2.1','Pasivo Corriente','pasivo','acreedora','2',2,0,'base',1,'2026-03-16 23:25:18','2026-03-16 23:25:18'),
(39,NULL,'2.1.01','Cuentas y documentos por pagar','pasivo','acreedora','2.1',3,0,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(40,NULL,'2.1.01.01','Proveedores locales por pagar','pasivo','acreedora','2.1.01',4,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(41,NULL,'2.1.01.02','Documentos por pagar proveedores','pasivo','acreedora','2.1.01',4,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(42,NULL,'2.1.02','Obligaciones con instituciones financieras CP','pasivo','acreedora','2.1',3,0,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(43,NULL,'2.1.02.01','Préstamos bancarios CP','pasivo','acreedora','2.1.02',4,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(44,NULL,'2.1.02.02','Sobregiros bancarios','pasivo','acreedora','2.1.02',4,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(45,NULL,'2.1.03','Provisiones corrientes','pasivo','acreedora','2.1',3,0,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(46,NULL,'2.1.03.01','Provisión jubilación patronal','pasivo','acreedora','2.1.03',4,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(47,NULL,'2.1.03.02','Provisión desahucio','pasivo','acreedora','2.1.03',4,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(48,NULL,'2.1.04','Otras obligaciones corrientes','pasivo','acreedora','2.1',3,0,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(49,NULL,'2.1.04.01','IVA en ventas por pagar','pasivo','acreedora','2.1.04',4,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(50,NULL,'2.1.04.02','Retenciones IVA por pagar','pasivo','acreedora','2.1.04',4,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(51,NULL,'2.1.04.03','Retenciones fuente por pagar','pasivo','acreedora','2.1.04',4,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(52,NULL,'2.1.04.04','IESS por pagar aporte patronal','pasivo','acreedora','2.1.04',4,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(53,NULL,'2.1.04.05','IESS por pagar aporte personal','pasivo','acreedora','2.1.04',4,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(54,NULL,'2.1.04.06','Décimo tercer sueldo por pagar','pasivo','acreedora','2.1.04',4,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(55,NULL,'2.1.04.07','Décimo cuarto sueldo por pagar','pasivo','acreedora','2.1.04',4,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(56,NULL,'2.1.04.08','Vacaciones por pagar','pasivo','acreedora','2.1.04',4,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(57,NULL,'2.1.04.09','Utilidades trabajadores por pagar','pasivo','acreedora','2.1.04',4,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(58,NULL,'2.1.04.10','Impuesto a la renta por pagar','pasivo','acreedora','2.1.04',4,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(59,NULL,'2.1.05','Anticipos de clientes','pasivo','acreedora','2.1',3,0,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(60,NULL,'2.1.05.01','Anticipos de clientes corriente','pasivo','acreedora','2.1.05',4,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(61,NULL,'2.2','Pasivo No Corriente','pasivo','acreedora','2',2,0,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(62,NULL,'2.2.01','Obligaciones financieras LP','pasivo','acreedora','2.2',3,0,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(63,NULL,'2.2.01.01','Préstamos bancarios LP','pasivo','acreedora','2.2.01',4,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(64,NULL,'2.2.05','Provisiones por beneficios a empleados','pasivo','acreedora','2.2',3,0,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(65,NULL,'2.2.05.01','Provisión jubilación patronal LP','pasivo','acreedora','2.2.05',4,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(66,NULL,'2.2.05.02','Provisión desahucio LP','pasivo','acreedora','2.2.05',4,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(67,NULL,'3.1','Capital','patrimonio','acreedora','3',2,0,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(68,NULL,'3.1.01','Capital suscrito y pagado','patrimonio','acreedora','3.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(69,NULL,'3.1.02','(-) Capital suscrito no pagado','patrimonio','deudora','3.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(70,NULL,'3.4','Reservas','patrimonio','acreedora','3',2,0,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(71,NULL,'3.4.01','Reserva legal','patrimonio','acreedora','3.4',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(72,NULL,'3.4.02','Reserva estatutaria','patrimonio','acreedora','3.4',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(73,NULL,'3.4.03','Reserva facultativa','patrimonio','acreedora','3.4',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(74,NULL,'3.6','Resultados acumulados','patrimonio','acreedora','3',2,0,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(75,NULL,'3.6.01','Ganancias acumuladas','patrimonio','acreedora','3.6',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(76,NULL,'3.6.02','(-) Pérdidas acumuladas','patrimonio','deudora','3.6',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(77,NULL,'3.7','Resultados del ejercicio','patrimonio','acreedora','3',2,0,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(78,NULL,'3.7.01','Ganancia neta del período','patrimonio','acreedora','3.7',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(79,NULL,'3.7.02','(-) Pérdida neta del período','patrimonio','deudora','3.7',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(80,NULL,'4.1','Ingresos de actividades ordinarias','ingreso','acreedora','4',2,0,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(81,NULL,'4.3','Otros ingresos','ingreso','acreedora','4',2,0,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(82,NULL,'4.3.01','Intereses financieros ganados','ingreso','acreedora','4.3',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(83,NULL,'4.3.02','Otras rentas','ingreso','acreedora','4.3',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(84,NULL,'4.3.03','Utilidad en venta de activos','ingreso','acreedora','4.3',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(85,NULL,'6.1','Gastos de administración y ventas','gasto','deudora','6',2,0,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(86,NULL,'6.1.01','Sueldos y salarios','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(87,NULL,'6.1.02','Beneficios sociales','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(88,NULL,'6.1.03','Aporte patronal IESS','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(89,NULL,'6.1.04','Honorarios profesionales','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(90,NULL,'6.1.05','Arrendamiento operativo','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(91,NULL,'6.1.06','Mantenimiento y reparaciones','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(92,NULL,'6.1.07','Combustibles y lubricantes','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(93,NULL,'6.1.08','Suministros y materiales de oficina','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(94,NULL,'6.1.09','Transporte y movilización','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(95,NULL,'6.1.10','Publicidad y propaganda','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(96,NULL,'6.1.11','Seguros y reaseguros','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(97,NULL,'6.1.12','Depreciación activos fijos','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(98,NULL,'6.1.13','Amortización intangibles','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(99,NULL,'6.1.14','Servicios básicos','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(100,NULL,'6.1.15','Impuestos contribuciones y otros','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(101,NULL,'6.1.16','Gastos de gestión','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(102,NULL,'6.1.17','Gastos de viaje','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(103,NULL,'6.1.18','Comisiones en ventas','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(104,NULL,'6.1.19','Otros gastos administración y ventas','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(105,NULL,'6.2','Gastos financieros','gasto','deudora','6',2,0,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(106,NULL,'6.2.01','Intereses bancarios','gasto','deudora','6.2',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(107,NULL,'6.2.02','Comisiones bancarias','gasto','deudora','6.2',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(108,NULL,'6.2.03','Intereses y multas SRI','gasto','deudora','6.2',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(109,NULL,'6.3','Otros gastos','gasto','deudora','6',2,0,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(110,NULL,'6.3.01','Pérdida en venta de activos','gasto','deudora','6.3',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(111,NULL,'6.3.02','Gastos no deducibles','gasto','deudora','6.3',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(112,NULL,'6.3.03','Otros gastos','gasto','deudora','6.3',3,1,'base',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(113,NULL,'4.1.01','Ventas de bienes','ingreso','acreedora','4.1',3,1,'productos',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(114,NULL,'4.1.03','(-) Descuentos en ventas','ingreso','deudora','4.1',3,1,'productos',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(115,NULL,'4.1.04','(-) Devoluciones en ventas','ingreso','deudora','4.1',3,1,'productos',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(116,NULL,'1.1.03','Inventarios','activo','deudora','1.1',3,0,'productos',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(117,NULL,'1.1.03.04','Inventario de productos terminados','activo','deudora','1.1.03',4,1,'productos',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(118,NULL,'5.1','Costo de ventas y producción','costo','deudora','5',2,0,'productos',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(119,NULL,'5.1.04','Costo de productos terminados vendidos','costo','deudora','5.1',3,1,'productos',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(120,NULL,'4.1.02','Prestación de servicios','ingreso','acreedora','4.1',3,1,'servicios',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(121,NULL,'6.1.20','Costo directo de servicios prestados','gasto','deudora','6.1',3,1,'servicios',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(122,NULL,'6.1.21','Subcontratos de servicios','gasto','deudora','6.1',3,1,'servicios',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(123,NULL,'1.1.03.01','Inventario de insumos','activo','deudora','1.1.03',4,1,'manufactura',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(124,NULL,'1.1.03.02','Inventario de materias primas','activo','deudora','1.1.03',4,1,'manufactura',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(125,NULL,'1.1.03.03','Inventario de productos en proceso','activo','deudora','1.1.03',4,1,'manufactura',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(126,NULL,'1.1.03.05','(-) Provisión valor neto realizable','activo','acreedora','1.1.03',4,1,'manufactura',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(127,NULL,'5.1.01','Costo de materias primas utilizadas','costo','deudora','5.1',3,1,'manufactura',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(128,NULL,'5.1.02','Costo de insumos utilizados','costo','deudora','5.1',3,1,'manufactura',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(129,NULL,'5.1.03','Costo de mano de obra directa','costo','deudora','5.1',3,1,'manufactura',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(130,NULL,'5.1.05','Costos indirectos de fabricación','costo','deudora','5.1',3,1,'manufactura',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(131,NULL,'1.1.06','Otros activos corrientes','activo','deudora','1.1',3,0,'logistica',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(132,NULL,'1.1.06.01','Materiales de empaque y embalaje','activo','deudora','1.1.06',4,1,'logistica',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(133,NULL,'1.1.06.02','Suministros de almacén','activo','deudora','1.1.06',4,1,'logistica',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(134,NULL,'1.2.01.08','Equipos de almacenamiento','activo','deudora','1.2.01',4,1,'logistica',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(135,NULL,'1.2.01.09','(-) Depreciación equipos almacenamiento','activo','acreedora','1.2.01',4,1,'logistica',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(136,NULL,'6.1.22','Gastos de almacenamiento y bodegaje','gasto','deudora','6.1',3,1,'logistica',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(137,NULL,'6.1.23','Fletes y acarreos locales','gasto','deudora','6.1',3,1,'logistica',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(138,NULL,'6.1.24','Embalaje y empaque','gasto','deudora','6.1',3,1,'logistica',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(139,NULL,'6.1.25','Seguros de mercadería en tránsito','gasto','deudora','6.1',3,1,'logistica',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(140,NULL,'6.1.26','Gastos de distribución','gasto','deudora','6.1',3,1,'logistica',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(141,NULL,'1.1.07','Importaciones en tránsito','activo','deudora','1.1',3,0,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(142,NULL,'1.1.07.01','Mercadería en tránsito importación','activo','deudora','1.1.07',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(143,NULL,'1.1.07.02','Anticipos a proveedores exterior','activo','deudora','1.1.07',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(144,NULL,'1.1.05.04','IVA en importaciones','activo','deudora','1.1.05',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(145,NULL,'2.1.01.03','Proveedores del exterior por pagar','pasivo','acreedora','2.1.01',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(146,NULL,'2.1.06','Otros pasivos corrientes','pasivo','acreedora','2.1',3,0,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(147,NULL,'2.1.06.01','Derechos arancelarios por pagar','pasivo','acreedora','2.1.06',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(148,NULL,'2.1.06.02','FODINFA por pagar','pasivo','acreedora','2.1.06',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(149,NULL,'2.1.06.03','Salvaguardias por pagar','pasivo','acreedora','2.1.06',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(150,NULL,'6.1.27','Gastos de importación','gasto','deudora','6.1',3,0,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(151,NULL,'6.1.27.01','Derechos arancelarios','gasto','deudora','6.1.27',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(152,NULL,'6.1.27.02','FODINFA','gasto','deudora','6.1.27',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(153,NULL,'6.1.27.03','Gastos de agente de aduana','gasto','deudora','6.1.27',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(154,NULL,'6.1.27.04','Flete internacional importación','gasto','deudora','6.1.27',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(155,NULL,'6.1.27.05','Seguro internacional importación','gasto','deudora','6.1.27',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(156,NULL,'6.1.27.06','Gastos portuarios importación','gasto','deudora','6.1.27',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(157,NULL,'6.1.27.07','Otros gastos de importación','gasto','deudora','6.1.27',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(158,NULL,'1.1.08','Exportaciones por cobrar','activo','deudora','1.1',3,0,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(159,NULL,'1.1.08.01','Cuentas por cobrar exterior','activo','deudora','1.1.08',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(160,NULL,'1.1.08.02','Documentos por cobrar exterior','activo','deudora','1.1.08',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(161,NULL,'1.1.05.05','Crédito tributario IVA exportaciones','activo','deudora','1.1.05',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(162,NULL,'4.1.05','Ventas al exterior','ingreso','acreedora','4.1',3,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(163,NULL,'4.1.06','(-) Descuentos en ventas exterior','ingreso','deudora','4.1',3,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(164,NULL,'6.1.28','Gastos de exportación','gasto','deudora','6.1',3,0,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(165,NULL,'6.1.28.01','Flete internacional exportación','gasto','deudora','6.1.28',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(166,NULL,'6.1.28.02','Seguro internacional exportación','gasto','deudora','6.1.28',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(167,NULL,'6.1.28.03','Gastos agente de aduana exportación','gasto','deudora','6.1.28',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(168,NULL,'6.1.28.04','Gastos portuarios exportación','gasto','deudora','6.1.28',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(169,NULL,'6.1.28.05','Certificados de origen','gasto','deudora','6.1.28',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(170,NULL,'6.1.28.06','Otros gastos de exportación','gasto','deudora','6.1.28',4,1,'comercio_exterior',1,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(171,1,'1','ACTIVO','activo','deudora',NULL,1,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(172,1,'2','PASIVO','pasivo','acreedora',NULL,1,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(173,1,'3','PATRIMONIO NETO','patrimonio','acreedora',NULL,1,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(174,1,'4','INGRESOS','ingreso','acreedora',NULL,1,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(175,1,'5','COSTOS','costo','deudora',NULL,1,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(176,1,'6','GASTOS','gasto','deudora',NULL,1,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(177,1,'1.1','Activo Corriente','activo','deudora','1',2,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(178,1,'1.1.01','Efectivo y equivalentes del efectivo','activo','deudora','1.1',3,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(179,1,'1.1.01.01','Caja general','activo','deudora','1.1.01',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(180,1,'1.1.01.02','Caja chica','activo','deudora','1.1.01',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(181,1,'1.1.01.03','Bancos cuenta corriente','activo','deudora','1.1.01',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(182,1,'1.1.01.04','Bancos cuenta de ahorros','activo','deudora','1.1.01',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(183,1,'1.1.02','Activos financieros','activo','deudora','1.1',3,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(184,1,'1.1.02.01','Cuentas por cobrar clientes','activo','deudora','1.1.02',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(185,1,'1.1.02.02','Documentos por cobrar','activo','deudora','1.1.02',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(186,1,'1.1.02.03','(-) Provisión cuentas incobrables','activo','acreedora','1.1.02',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(187,1,'1.1.04','Servicios y otros pagos anticipados','activo','deudora','1.1',3,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(188,1,'1.1.04.01','Seguros pagados por anticipado','activo','deudora','1.1.04',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(189,1,'1.1.04.02','Arriendos pagados por anticipado','activo','deudora','1.1.04',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(190,1,'1.1.04.03','Anticipos a proveedores','activo','deudora','1.1.04',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(191,1,'1.1.05','Activos por impuestos corrientes','activo','deudora','1.1',3,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(192,1,'1.1.05.01','Crédito tributario IVA','activo','deudora','1.1.05',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(193,1,'1.1.05.02','Crédito tributario impuesto a la renta','activo','deudora','1.1.05',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(194,1,'1.1.05.03','Anticipo impuesto a la renta','activo','deudora','1.1.05',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(195,1,'1.2','Activo No Corriente','activo','deudora','1',2,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(196,1,'1.2.01','Propiedades, planta y equipo','activo','deudora','1.2',3,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(197,1,'1.2.01.01','Terrenos','activo','deudora','1.2.01',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(198,1,'1.2.01.02','Edificios y construcciones','activo','deudora','1.2.01',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(199,1,'1.2.01.03','Muebles y enseres','activo','deudora','1.2.01',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(200,1,'1.2.01.04','Maquinaria y equipo','activo','deudora','1.2.01',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(201,1,'1.2.01.05','Equipo de computación','activo','deudora','1.2.01',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(202,1,'1.2.01.06','Vehículos','activo','deudora','1.2.01',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(203,1,'1.2.01.07','(-) Depreciación acumulada PPE','activo','acreedora','1.2.01',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(204,1,'1.2.04','Activo intangible','activo','deudora','1.2',3,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(205,1,'1.2.04.01','Marcas y patentes','activo','deudora','1.2.04',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(206,1,'1.2.04.02','Programas de computación','activo','deudora','1.2.04',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(207,1,'1.2.04.03','(-) Amortización acumulada intangibles','activo','acreedora','1.2.04',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(208,1,'2.1','Pasivo Corriente','pasivo','acreedora','2',2,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(209,1,'2.1.01','Cuentas y documentos por pagar','pasivo','acreedora','2.1',3,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(210,1,'2.1.01.01','Proveedores locales por pagar','pasivo','acreedora','2.1.01',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(211,1,'2.1.01.02','Documentos por pagar proveedores','pasivo','acreedora','2.1.01',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(212,1,'2.1.02','Obligaciones con instituciones financieras CP','pasivo','acreedora','2.1',3,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(213,1,'2.1.02.01','Préstamos bancarios CP','pasivo','acreedora','2.1.02',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(214,1,'2.1.02.02','Sobregiros bancarios','pasivo','acreedora','2.1.02',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(215,1,'2.1.03','Provisiones corrientes','pasivo','acreedora','2.1',3,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(216,1,'2.1.03.01','Provisión jubilación patronal','pasivo','acreedora','2.1.03',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(217,1,'2.1.03.02','Provisión desahucio','pasivo','acreedora','2.1.03',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(218,1,'2.1.04','Otras obligaciones corrientes','pasivo','acreedora','2.1',3,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(219,1,'2.1.04.01','IVA en ventas por pagar','pasivo','acreedora','2.1.04',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(220,1,'2.1.04.02','Retenciones IVA por pagar','pasivo','acreedora','2.1.04',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(221,1,'2.1.04.03','Retenciones fuente por pagar','pasivo','acreedora','2.1.04',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(222,1,'2.1.04.04','IESS por pagar aporte patronal','pasivo','acreedora','2.1.04',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(223,1,'2.1.04.05','IESS por pagar aporte personal','pasivo','acreedora','2.1.04',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(224,1,'2.1.04.06','Décimo tercer sueldo por pagar','pasivo','acreedora','2.1.04',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(225,1,'2.1.04.07','Décimo cuarto sueldo por pagar','pasivo','acreedora','2.1.04',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(226,1,'2.1.04.08','Vacaciones por pagar','pasivo','acreedora','2.1.04',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(227,1,'2.1.04.09','Utilidades trabajadores por pagar','pasivo','acreedora','2.1.04',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(228,1,'2.1.04.10','Impuesto a la renta por pagar','pasivo','acreedora','2.1.04',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(229,1,'2.1.05','Anticipos de clientes','pasivo','acreedora','2.1',3,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(230,1,'2.1.05.01','Anticipos de clientes corriente','pasivo','acreedora','2.1.05',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(231,1,'2.2','Pasivo No Corriente','pasivo','acreedora','2',2,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(232,1,'2.2.01','Obligaciones financieras LP','pasivo','acreedora','2.2',3,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(233,1,'2.2.01.01','Préstamos bancarios LP','pasivo','acreedora','2.2.01',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(234,1,'2.2.05','Provisiones por beneficios a empleados','pasivo','acreedora','2.2',3,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(235,1,'2.2.05.01','Provisión jubilación patronal LP','pasivo','acreedora','2.2.05',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(236,1,'2.2.05.02','Provisión desahucio LP','pasivo','acreedora','2.2.05',4,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(237,1,'3.1','Capital','patrimonio','acreedora','3',2,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(238,1,'3.1.01','Capital suscrito y pagado','patrimonio','acreedora','3.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(239,1,'3.1.02','(-) Capital suscrito no pagado','patrimonio','deudora','3.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(240,1,'3.4','Reservas','patrimonio','acreedora','3',2,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(241,1,'3.4.01','Reserva legal','patrimonio','acreedora','3.4',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(242,1,'3.4.02','Reserva estatutaria','patrimonio','acreedora','3.4',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(243,1,'3.4.03','Reserva facultativa','patrimonio','acreedora','3.4',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(244,1,'3.6','Resultados acumulados','patrimonio','acreedora','3',2,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(245,1,'3.6.01','Ganancias acumuladas','patrimonio','acreedora','3.6',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(246,1,'3.6.02','(-) Pérdidas acumuladas','patrimonio','deudora','3.6',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(247,1,'3.7','Resultados del ejercicio','patrimonio','acreedora','3',2,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(248,1,'3.7.01','Ganancia neta del período','patrimonio','acreedora','3.7',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(249,1,'3.7.02','(-) Pérdida neta del período','patrimonio','deudora','3.7',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(250,1,'4.1','Ingresos de actividades ordinarias','ingreso','acreedora','4',2,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(251,1,'4.3','Otros ingresos','ingreso','acreedora','4',2,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(252,1,'4.3.01','Intereses financieros ganados','ingreso','acreedora','4.3',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(253,1,'4.3.02','Otras rentas','ingreso','acreedora','4.3',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(254,1,'4.3.03','Utilidad en venta de activos','ingreso','acreedora','4.3',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(255,1,'6.1','Gastos de administración y ventas','gasto','deudora','6',2,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(256,1,'6.1.01','Sueldos y salarios','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(257,1,'6.1.02','Beneficios sociales','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(258,1,'6.1.03','Aporte patronal IESS','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(259,1,'6.1.04','Honorarios profesionales','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(260,1,'6.1.05','Arrendamiento operativo','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(261,1,'6.1.06','Mantenimiento y reparaciones','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(262,1,'6.1.07','Combustibles y lubricantes','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(263,1,'6.1.08','Suministros y materiales de oficina','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(264,1,'6.1.09','Transporte y movilización','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(265,1,'6.1.10','Publicidad y propaganda','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(266,1,'6.1.11','Seguros y reaseguros','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(267,1,'6.1.12','Depreciación activos fijos','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(268,1,'6.1.13','Amortización intangibles','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(269,1,'6.1.14','Servicios básicos','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(270,1,'6.1.15','Impuestos contribuciones y otros','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(271,1,'6.1.16','Gastos de gestión','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(272,1,'6.1.17','Gastos de viaje','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(273,1,'6.1.18','Comisiones en ventas','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(274,1,'6.1.19','Otros gastos administración y ventas','gasto','deudora','6.1',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(275,1,'6.2','Gastos financieros','gasto','deudora','6',2,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(276,1,'6.2.01','Intereses bancarios','gasto','deudora','6.2',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(277,1,'6.2.02','Comisiones bancarias','gasto','deudora','6.2',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(278,1,'6.2.03','Intereses y multas SRI','gasto','deudora','6.2',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(279,1,'6.3','Otros gastos','gasto','deudora','6',2,0,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(280,1,'6.3.01','Pérdida en venta de activos','gasto','deudora','6.3',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(281,1,'6.3.02','Gastos no deducibles','gasto','deudora','6.3',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(282,1,'6.3.03','Otros gastos','gasto','deudora','6.3',3,1,'base',1,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(283,1,'4.1.01','Ventas de bienes','ingreso','acreedora','4.1',3,1,'productos',1,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(284,1,'4.1.03','(-) Descuentos en ventas','ingreso','deudora','4.1',3,1,'productos',1,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(285,1,'4.1.04','(-) Devoluciones en ventas','ingreso','deudora','4.1',3,1,'productos',1,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(286,1,'1.1.03','Inventarios','activo','deudora','1.1',3,0,'productos',1,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(287,1,'1.1.03.04','Inventario de productos terminados','activo','deudora','1.1.03',4,1,'productos',1,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(288,1,'5.1','Costo de ventas y producción','costo','deudora','5',2,0,'productos',1,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(289,1,'5.1.04','Costo de productos terminados vendidos','costo','deudora','5.1',3,1,'productos',1,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(290,1,'1.1.03.01','Inventario de insumos','activo','deudora','1.1.03',4,1,'manufactura',1,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(291,1,'5.1.02','Costo de insumos utilizados','costo','deudora','5.1',3,1,'manufactura',1,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(292,1,'1.1.03.02','Inventario de materias primas','activo','deudora','1.1.03',4,1,'manufactura',1,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(293,1,'5.1.01','Costo de materias primas utilizadas','costo','deudora','5.1',3,1,'manufactura',1,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(294,1,'1.1.03.03','Inventario de productos en proceso','activo','deudora','1.1.03',4,1,'manufactura',1,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(295,1,'6.1.20','Costo directo de servicios prestados','gasto','deudora','6.1',3,1,'servicios',1,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(296,1,'4.1.02','Prestación de servicios','ingreso','acreedora','4.1',3,1,'servicios',1,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(297,1,'2.1.01.03','Proveedores del exterior por pagar','pasivo','acreedora','2.1.01',4,1,'comercio_exterior',1,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(300,NULL,'2.1.02.03','Tarjetas de crédito por pagar','pasivo','acreedora','2.1.02',4,1,'base',1,'2026-03-17 03:33:35','2026-03-17 03:33:35'),
(301,1,'2.1.02.03','Tarjetas de crédito por pagar','pasivo','acreedora','2.1.02',4,1,'base',1,'2026-03-17 03:33:35','2026-03-17 03:33:35');
/*!40000 ALTER TABLE `account_plans` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `accounting_maps`
--

DROP TABLE IF EXISTS `accounting_maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounting_maps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint(20) unsigned DEFAULT NULL,
  `tipo_item` enum('insumo','materia_prima','producto_en_proceso','producto_terminado','activo_fijo_maquinaria','activo_fijo_computo','activo_fijo_vehiculo','activo_fijo_muebles','servicio','global') NOT NULL,
  `tipo_movimiento` enum('compra_contado','compra_credito_local','compra_credito_exterior','venta_contado','venta_credito','consumo_produccion','costo_venta','iva_compras','iva_ventas','depreciacion','ajuste_inventario','ajuste_sobrante','entrada_produccion','salida_produccion') NOT NULL,
  `account_plan_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_map_unique` (`empresa_id`,`tipo_item`,`tipo_movimiento`),
  KEY `accounting_maps_account_plan_id_foreign` (`account_plan_id`),
  CONSTRAINT `accounting_maps_account_plan_id_foreign` FOREIGN KEY (`account_plan_id`) REFERENCES `account_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_maps_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_maps`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `accounting_maps` WRITE;
/*!40000 ALTER TABLE `accounting_maps` DISABLE KEYS */;
INSERT INTO `accounting_maps` VALUES
(1,NULL,'insumo','compra_contado',123,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(2,NULL,'insumo','compra_credito_local',123,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(3,NULL,'insumo','compra_credito_exterior',123,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(4,NULL,'insumo','consumo_produccion',128,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(5,NULL,'insumo','iva_compras',22,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(6,NULL,'insumo','ajuste_inventario',112,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(7,NULL,'insumo','ajuste_sobrante',83,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(8,NULL,'materia_prima','compra_contado',124,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(9,NULL,'materia_prima','compra_credito_local',124,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(10,NULL,'materia_prima','compra_credito_exterior',124,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(11,NULL,'materia_prima','consumo_produccion',127,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(12,NULL,'materia_prima','iva_compras',22,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(13,NULL,'materia_prima','ajuste_inventario',112,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(14,NULL,'materia_prima','ajuste_sobrante',83,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(15,NULL,'producto_terminado','venta_contado',113,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(16,NULL,'producto_terminado','venta_credito',113,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(17,NULL,'producto_terminado','costo_venta',119,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(18,NULL,'producto_terminado','iva_ventas',49,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(19,NULL,'producto_en_proceso','consumo_produccion',125,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(20,NULL,'activo_fijo_maquinaria','compra_contado',30,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(21,NULL,'activo_fijo_maquinaria','compra_credito_local',30,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(22,NULL,'activo_fijo_maquinaria','depreciacion',33,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(23,NULL,'activo_fijo_computo','compra_contado',31,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(24,NULL,'activo_fijo_computo','compra_credito_local',31,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(25,NULL,'activo_fijo_computo','depreciacion',33,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(26,NULL,'activo_fijo_vehiculo','compra_contado',32,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(27,NULL,'activo_fijo_vehiculo','compra_credito_local',32,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(28,NULL,'activo_fijo_vehiculo','depreciacion',33,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(29,NULL,'activo_fijo_muebles','compra_contado',29,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(30,NULL,'activo_fijo_muebles','compra_credito_local',29,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(31,NULL,'activo_fijo_muebles','depreciacion',33,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(32,NULL,'servicio','compra_contado',121,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(33,NULL,'servicio','compra_credito_local',121,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(34,NULL,'servicio','iva_compras',22,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(35,NULL,'servicio','venta_contado',120,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(36,NULL,'servicio','venta_credito',120,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(37,NULL,'servicio','iva_ventas',49,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(38,NULL,'global','compra_contado',11,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(39,NULL,'global','compra_credito_local',40,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(40,NULL,'global','compra_credito_exterior',145,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(41,NULL,'global','venta_contado',11,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(42,NULL,'global','venta_credito',14,'2026-03-16 23:25:19','2026-03-16 23:25:19'),
(43,1,'insumo','iva_compras',192,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(44,1,'insumo','ajuste_inventario',282,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(45,1,'insumo','ajuste_sobrante',253,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(46,1,'materia_prima','iva_compras',192,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(47,1,'materia_prima','ajuste_inventario',282,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(48,1,'materia_prima','ajuste_sobrante',253,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(49,1,'producto_terminado','iva_ventas',219,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(50,1,'activo_fijo_maquinaria','compra_contado',200,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(51,1,'activo_fijo_maquinaria','compra_credito_local',200,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(52,1,'activo_fijo_maquinaria','depreciacion',203,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(53,1,'activo_fijo_computo','compra_contado',201,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(54,1,'activo_fijo_computo','compra_credito_local',201,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(55,1,'activo_fijo_computo','depreciacion',203,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(56,1,'activo_fijo_vehiculo','compra_contado',202,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(57,1,'activo_fijo_vehiculo','compra_credito_local',202,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(58,1,'activo_fijo_vehiculo','depreciacion',203,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(59,1,'activo_fijo_muebles','compra_contado',199,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(60,1,'activo_fijo_muebles','compra_credito_local',199,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(61,1,'activo_fijo_muebles','depreciacion',203,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(62,1,'servicio','iva_compras',192,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(63,1,'servicio','iva_ventas',219,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(64,1,'global','compra_contado',181,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(65,1,'global','compra_credito_local',210,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(66,1,'global','venta_contado',181,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(67,1,'global','venta_credito',184,'2026-03-16 23:50:22','2026-03-16 23:50:22'),
(87,NULL,'producto_terminado','compra_contado',117,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(88,NULL,'producto_terminado','compra_credito_local',117,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(89,NULL,'producto_terminado','compra_credito_exterior',117,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(90,NULL,'producto_terminado','iva_compras',22,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(91,NULL,'producto_terminado','ajuste_inventario',112,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(92,NULL,'producto_terminado','ajuste_sobrante',83,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(93,NULL,'producto_terminado','entrada_produccion',117,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(94,NULL,'producto_terminado','salida_produccion',117,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(95,NULL,'materia_prima','salida_produccion',124,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(96,NULL,'materia_prima','entrada_produccion',124,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(97,NULL,'insumo','salida_produccion',123,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(98,NULL,'insumo','entrada_produccion',123,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(99,NULL,'global','iva_compras',22,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(100,NULL,'global','iva_ventas',49,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(101,1,'producto_terminado','compra_contado',287,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(102,1,'producto_terminado','compra_credito_local',287,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(103,1,'producto_terminado','compra_credito_exterior',287,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(104,1,'producto_terminado','iva_compras',192,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(105,1,'producto_terminado','ajuste_inventario',282,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(106,1,'producto_terminado','ajuste_sobrante',253,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(107,1,'producto_terminado','entrada_produccion',287,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(108,1,'producto_terminado','salida_produccion',287,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(109,1,'global','iva_compras',192,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(110,1,'global','iva_ventas',219,'2026-03-17 02:16:35','2026-03-17 02:16:35'),
(111,1,'insumo','compra_contado',290,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(112,1,'insumo','compra_credito_local',290,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(113,1,'insumo','compra_credito_exterior',290,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(114,1,'insumo','consumo_produccion',291,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(115,1,'materia_prima','compra_contado',292,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(116,1,'materia_prima','compra_credito_local',292,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(117,1,'materia_prima','compra_credito_exterior',292,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(118,1,'materia_prima','consumo_produccion',293,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(119,1,'producto_terminado','venta_contado',283,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(120,1,'producto_terminado','venta_credito',283,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(121,1,'producto_terminado','costo_venta',289,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(122,1,'producto_en_proceso','consumo_produccion',294,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(123,1,'servicio','compra_contado',295,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(124,1,'servicio','compra_credito_local',295,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(125,1,'servicio','venta_contado',296,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(126,1,'servicio','venta_credito',296,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(127,1,'global','compra_credito_exterior',297,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(128,1,'materia_prima','salida_produccion',292,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(129,1,'materia_prima','entrada_produccion',292,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(130,1,'insumo','salida_produccion',290,'2026-03-17 02:39:54','2026-03-17 02:39:54'),
(131,1,'insumo','entrada_produccion',290,'2026-03-17 02:39:54','2026-03-17 02:39:54');
/*!40000 ALTER TABLE `accounting_maps` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `bank_accounts`
--

DROP TABLE IF EXISTS `bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint(20) unsigned NOT NULL,
  `bank_id` bigint(20) unsigned NOT NULL,
  `numero_cuenta` varchar(255) NOT NULL,
  `tipo_cuenta` enum('corriente','ahorros') NOT NULL,
  `nombre_titular` varchar(255) NOT NULL,
  `account_plan_id` bigint(20) unsigned DEFAULT NULL,
  `saldo_inicial` decimal(14,2) NOT NULL DEFAULT 0.00,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_accounts_empresa_id_foreign` (`empresa_id`),
  KEY `bank_accounts_bank_id_foreign` (`bank_id`),
  KEY `bank_accounts_account_plan_id_foreign` (`account_plan_id`),
  CONSTRAINT `bank_accounts_account_plan_id_foreign` FOREIGN KEY (`account_plan_id`) REFERENCES `account_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bank_accounts_bank_id_foreign` FOREIGN KEY (`bank_id`) REFERENCES `banks` (`id`),
  CONSTRAINT `bank_accounts_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_accounts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `bank_accounts` WRITE;
/*!40000 ALTER TABLE `bank_accounts` DISABLE KEYS */;
INSERT INTO `bank_accounts` VALUES
(1,1,3,'27059121829','corriente','RIVETECUADOR',181,5000.00,1,'2026-03-17 03:10:17','2026-03-17 03:10:17');
/*!40000 ALTER TABLE `bank_accounts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `banks`
--

DROP TABLE IF EXISTS `banks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `banks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `tipo` enum('banco_privado','banco_publico','cooperativa','mutualista','financiera') NOT NULL,
  `codigo_sbs` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banks`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `banks` WRITE;
/*!40000 ALTER TABLE `banks` DISABLE KEYS */;
INSERT INTO `banks` VALUES
(1,'Banco Pichincha','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(2,'Banco de Guayaquil','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(3,'Produbanco','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(4,'Banco del Pacífico','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(5,'Banco Internacional','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(6,'Banco Bolivariano','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(7,'Banco General Rumiñahui','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(8,'Banco Solidario','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(9,'Banco del Austro','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(10,'Banco Coopnacional','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(11,'Banco Amazonas','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(12,'Banco D-Miro','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(13,'Banco Comercial de Manabí','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(14,'Banco ProCredit','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(15,'Banco Capital','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(16,'Banco Loja','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(17,'Banco de Machala','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(18,'Banco VisionFund Ecuador','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(19,'Banco Finca','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(20,'Banco Delbank','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(21,'Banco Acceso','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(22,'Banco Territorial','banco_privado',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(23,'BanEcuador B.P.','banco_publico',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(24,'Banco del Estado (BEDE)','banco_publico',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(25,'Corporación Financiera Nacional (CFN)','banco_publico',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(26,'Banco Central del Ecuador','banco_publico',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(27,'Instituto Ecuatoriano de Crédito Educativo (IECE)','banco_publico',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(28,'JEP (Juventud Ecuatoriana Progresista)','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(29,'Jardín Azuayo','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(30,'Policía Nacional','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(31,'29 de Octubre','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(32,'Cooprogreso','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(33,'Mushuc Runa','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(34,'Mego','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(35,'San Francisco','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(36,'OSCUS','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(37,'Cacpeco','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(38,'Pablo Muñoz Vega','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(39,'Atuntaqui','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(40,'Riobamba','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(41,'El Sagrario','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(42,'Andalucía','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(43,'Alianza del Valle','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(44,'Cotocollao','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(45,'Fernando Daquilema','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(46,'Cámara de Comercio de Quito','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(47,'Luz del Valle','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(48,'Chibuleo','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(49,'Kullki Wasi','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(50,'Pilahuin Tío','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(51,'Ejército','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(52,'Santa Rosa','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(53,'Tulcán','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(54,'23 de Julio','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(55,'Educadores de Tungurahua','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(56,'Sumak Kawsay','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(57,'Virgen del Cisne','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(58,'Coopmego','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(59,'Calceta','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(60,'Tena','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(61,'15 de Abril','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(62,'9 de Octubre','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(63,'Acción Rural','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(64,'Baños','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(65,'Camino de la Prosperidad','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(66,'Chone Ltda.','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(67,'Cía Ltda. Nueva Huancavilca','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(68,'Ciudad de Zamora','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(69,'Coca Ltda.','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(70,'Comercio','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(71,'Cop. de Ahorro y Crédito de la Pequeña Empresa de Pastaza','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(72,'COOPAC Austro','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(73,'Credi Ya','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(74,'El Cambio','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(75,'Fasayñan','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(76,'Guaranda','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(77,'Huaquillas','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(78,'Intercultural Financiera','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(79,'Juventud Unida','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(80,'La Merced','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(81,'Loja Internacional','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(82,'Manantial de Oro','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(83,'Metropolitana','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(84,'Minga','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(85,'Mushuk Yuyay','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(86,'Nueva Jerusalén','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(87,'Padre Julián Lorente','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(88,'Pan American','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(89,'Previsión, Ahorro y Desarrollo (PRADEC)','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(90,'Puéllaro','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(91,'Pusará','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(92,'Quilanga','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(93,'San Gabriel','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(94,'San José (Chimborazo)','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(95,'San José de Guaslán','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(96,'San Miguel de Los Bancos','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(97,'San Pedro de Taboada','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(98,'Santa Ana','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(99,'Santa Bárbara','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(100,'SERVIDORES PÚBLICOS del Ministerio de Educación y Cultura','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(101,'Sociedad Protectora del Trabajador Magisterial','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(102,'Sol de los Andes','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(103,'Textil 14 de Marzo','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(104,'Unión El Ejido','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(105,'Vencedores','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(106,'Visión de los Andes','cooperativa',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(107,'Mutualista Pichincha','mutualista',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(108,'Mutualista Azuay','mutualista',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(109,'Mutualista Imbabura','mutualista',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(110,'Mutualista Ambato','mutualista',NULL,1,'2026-03-17 03:07:02','2026-03-17 03:07:33'),
(111,'Diners Club del Ecuador','financiera',NULL,1,'2026-03-17 03:07:33','2026-03-17 03:07:33'),
(112,'Servicios y Cobranzas Interdin','financiera',NULL,1,'2026-03-17 03:07:33','2026-03-17 03:07:33'),
(113,'Vazcorp Sociedad Financiera','financiera',NULL,1,'2026-03-17 03:07:33','2026-03-17 03:07:33');
/*!40000 ALTER TABLE `banks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES
('mashaec-erp-cache-356a192b7913b04c54574d18c28d46e6395428ab','i:1;',1773699372),
('mashaec-erp-cache-356a192b7913b04c54574d18c28d46e6395428ab:timer','i:1773699372;',1773699372),
('mashaec-erp-cache-livewire-rate-limiter:a17961fa74e9275d529f489537f179c05d50c2f3','i:1;',1773687110),
('mashaec-erp-cache-livewire-rate-limiter:a17961fa74e9275d529f489537f179c05d50c2f3:timer','i:1773687110;',1773687110),
('mashaec-erp-cache-spatie.permission.cache','a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:10:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:12:\"reportes.ver\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:17:\"reportes.exportar\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:15:\"proveedores.ver\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:17:\"proveedores.crear\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:18:\"proveedores.editar\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:20:\"proveedores.eliminar\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:14:\"inventario.ver\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:16:\"inventario.crear\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:4;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:17:\"inventario.editar\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:4;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:19:\"inventario.eliminar\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}}s:5:\"roles\";a:3:{i:0;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:13:\"admin_empresa\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:8:\"contador\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:10:\"inventario\";s:1:\"c\";s:3:\"web\";}}}',1773772094);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `cash_movements`
--

DROP TABLE IF EXISTS `cash_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint(20) unsigned NOT NULL,
  `cash_register_id` bigint(20) unsigned NOT NULL,
  `cash_session_id` bigint(20) unsigned DEFAULT NULL,
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  `tipo` enum('ingreso','egreso') NOT NULL,
  `origen` enum('venta','compra','transferencia','ajuste','apertura','cierre','manual') NOT NULL DEFAULT 'manual',
  `referencia_tipo` varchar(255) DEFAULT NULL,
  `referencia_id` bigint(20) unsigned DEFAULT NULL,
  `monto` decimal(14,2) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `fecha` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cash_movements_empresa_id_foreign` (`empresa_id`),
  KEY `cash_movements_cash_register_id_foreign` (`cash_register_id`),
  KEY `cash_movements_cash_session_id_foreign` (`cash_session_id`),
  KEY `cash_movements_journal_entry_id_foreign` (`journal_entry_id`),
  CONSTRAINT `cash_movements_cash_register_id_foreign` FOREIGN KEY (`cash_register_id`) REFERENCES `cash_registers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cash_movements_cash_session_id_foreign` FOREIGN KEY (`cash_session_id`) REFERENCES `cash_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cash_movements_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cash_movements_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_movements`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cash_movements` WRITE;
/*!40000 ALTER TABLE `cash_movements` DISABLE KEYS */;
/*!40000 ALTER TABLE `cash_movements` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `cash_registers`
--

DROP TABLE IF EXISTS `cash_registers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_registers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint(20) unsigned NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `tipo` enum('principal','chica') NOT NULL DEFAULT 'principal',
  `saldo_actual` decimal(14,2) NOT NULL DEFAULT 0.00,
  `account_plan_id` bigint(20) unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cash_registers_empresa_id_foreign` (`empresa_id`),
  KEY `cash_registers_account_plan_id_foreign` (`account_plan_id`),
  CONSTRAINT `cash_registers_account_plan_id_foreign` FOREIGN KEY (`account_plan_id`) REFERENCES `account_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cash_registers_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_registers`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cash_registers` WRITE;
/*!40000 ALTER TABLE `cash_registers` DISABLE KEYS */;
INSERT INTO `cash_registers` VALUES
(1,1,'COBROS EN EFECTIVO','principal',0.00,179,1,'2026-03-17 03:11:08','2026-03-17 03:11:08');
/*!40000 ALTER TABLE `cash_registers` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `cash_sessions`
--

DROP TABLE IF EXISTS `cash_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint(20) unsigned NOT NULL,
  `cash_register_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `fecha` date NOT NULL,
  `saldo_apertura` decimal(14,2) NOT NULL DEFAULT 0.00,
  `saldo_cierre` decimal(14,2) DEFAULT NULL,
  `total_ingresos` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_egresos` decimal(14,2) NOT NULL DEFAULT 0.00,
  `diferencia` decimal(14,2) DEFAULT NULL,
  `estado` enum('abierta','cerrada') NOT NULL DEFAULT 'abierta',
  `observaciones` text DEFAULT NULL,
  `apertura_at` timestamp NULL DEFAULT NULL,
  `cierre_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cash_sessions_empresa_id_foreign` (`empresa_id`),
  KEY `cash_sessions_cash_register_id_foreign` (`cash_register_id`),
  KEY `cash_sessions_user_id_foreign` (`user_id`),
  CONSTRAINT `cash_sessions_cash_register_id_foreign` FOREIGN KEY (`cash_register_id`) REFERENCES `cash_registers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cash_sessions_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cash_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_sessions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cash_sessions` WRITE;
/*!40000 ALTER TABLE `cash_sessions` DISABLE KEYS */;
INSERT INTO `cash_sessions` VALUES
(1,1,1,1,'2026-03-16',84.00,NULL,0.00,0.00,NULL,'abierta',NULL,'2026-03-17 03:14:12',NULL,'2026-03-17 03:14:24','2026-03-17 03:14:24');
/*!40000 ALTER TABLE `cash_sessions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `credit_card_movements`
--

DROP TABLE IF EXISTS `credit_card_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_card_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint(20) unsigned NOT NULL,
  `credit_card_id` bigint(20) unsigned NOT NULL,
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  `tipo` enum('cargo','pago','ajuste') NOT NULL,
  `referencia_tipo` varchar(255) DEFAULT NULL,
  `referencia_id` bigint(20) unsigned DEFAULT NULL,
  `monto` decimal(14,2) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `fecha` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_card_movements_empresa_id_foreign` (`empresa_id`),
  KEY `credit_card_movements_credit_card_id_foreign` (`credit_card_id`),
  KEY `credit_card_movements_journal_entry_id_foreign` (`journal_entry_id`),
  CONSTRAINT `credit_card_movements_credit_card_id_foreign` FOREIGN KEY (`credit_card_id`) REFERENCES `credit_cards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `credit_card_movements_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `credit_card_movements_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_card_movements`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `credit_card_movements` WRITE;
/*!40000 ALTER TABLE `credit_card_movements` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_card_movements` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `credit_cards`
--

DROP TABLE IF EXISTS `credit_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_cards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint(20) unsigned NOT NULL,
  `bank_id` bigint(20) unsigned NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `ultimos_digitos` varchar(4) DEFAULT NULL,
  `franquicia` enum('visa','mastercard','amex','diners') NOT NULL DEFAULT 'visa',
  `limite_credito` decimal(14,2) NOT NULL DEFAULT 0.00,
  `saldo_utilizado` decimal(14,2) NOT NULL DEFAULT 0.00,
  `dia_corte` int(11) NOT NULL DEFAULT 1,
  `dia_pago` int(11) NOT NULL DEFAULT 15,
  `account_plan_id` bigint(20) unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_cards_empresa_id_foreign` (`empresa_id`),
  KEY `credit_cards_bank_id_foreign` (`bank_id`),
  KEY `credit_cards_account_plan_id_foreign` (`account_plan_id`),
  CONSTRAINT `credit_cards_account_plan_id_foreign` FOREIGN KEY (`account_plan_id`) REFERENCES `account_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `credit_cards_bank_id_foreign` FOREIGN KEY (`bank_id`) REFERENCES `banks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `credit_cards_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_cards`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `credit_cards` WRITE;
/*!40000 ALTER TABLE `credit_cards` DISABLE KEYS */;
INSERT INTO `credit_cards` VALUES
(1,1,1,'DINERS','7126','diners',14000.00,0.00,15,1,301,1,'2026-03-17 03:13:57','2026-03-17 03:33:35');
/*!40000 ALTER TABLE `credit_cards` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint(20) unsigned NOT NULL,
  `codigo` varchar(255) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `tipo_persona` enum('natural','juridica') NOT NULL DEFAULT 'natural',
  `tipo_identificacion` enum('cedula','ruc','pasaporte','consumidor_final') NOT NULL DEFAULT 'ruc',
  `numero_identificacion` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefono` varchar(255) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `es_exportador` tinyint(1) NOT NULL DEFAULT 0,
  `pais_destino` varchar(255) DEFAULT NULL,
  `cuenta_contable_id` bigint(20) unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_empresa_id_codigo_unique` (`empresa_id`,`codigo`),
  UNIQUE KEY `customers_empresa_id_numero_identificacion_unique` (`empresa_id`,`numero_identificacion`),
  KEY `customers_cuenta_contable_id_foreign` (`cuenta_contable_id`),
  KEY `customers_codigo_index` (`codigo`),
  KEY `customers_numero_identificacion_index` (`numero_identificacion`),
  CONSTRAINT `customers_cuenta_contable_id_foreign` FOREIGN KEY (`cuenta_contable_id`) REFERENCES `account_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `empresas`
--

DROP TABLE IF EXISTS `empresas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `empresas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `tipo_persona` enum('natural','juridica') DEFAULT NULL,
  `tipo_identificacion` enum('ruc','cedula','pasaporte') DEFAULT NULL,
  `numero_identificacion` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `actividad_economica` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tiene_logistica` tinyint(1) NOT NULL DEFAULT 0,
  `tiene_comercio_exterior` tinyint(1) NOT NULL DEFAULT 0,
  `tipo_operacion_productos` tinyint(1) NOT NULL DEFAULT 1,
  `tipo_operacion_servicios` tinyint(1) NOT NULL DEFAULT 0,
  `tipo_operacion_manufactura` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresas_email_unique` (`email`),
  UNIQUE KEY `empresas_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empresas`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `empresas` WRITE;
/*!40000 ALTER TABLE `empresas` DISABLE KEYS */;
INSERT INTO `empresas` VALUES
(1,'Rivet Ecuador S.A.S.','rivet-ecuador-sas','empresa@rivet-ec.com','juridica','ruc','1793229485001','Pintag Via Tolontag','Industria alimenticia, desarrolla licores y alimentos. Su fuerza laboral es principalmente de mujeres ',1,'2026-03-16 23:50:22','2026-03-16 23:50:22',0,0,1,0,0);
/*!40000 ALTER TABLE `empresas` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `inventory_item_files`
--

DROP TABLE IF EXISTS `inventory_item_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_item_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inventory_item_id` bigint(20) unsigned NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `path` varchar(255) NOT NULL,
  `tipo` enum('ficha_tecnica','certificado','hoja_seguridad','otro') NOT NULL,
  `size` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_item_files_inventory_item_id_foreign` (`inventory_item_id`),
  CONSTRAINT `inventory_item_files_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_item_files`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `inventory_item_files` WRITE;
/*!40000 ALTER TABLE `inventory_item_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_item_files` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `inventory_items`
--

DROP TABLE IF EXISTS `inventory_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint(20) unsigned NOT NULL,
  `codigo` varchar(255) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `type` enum('insumo','materia_prima','producto_terminado','activo_fijo','servicio') NOT NULL,
  `measurement_unit_id` bigint(20) unsigned DEFAULT NULL,
  `account_plan_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_price` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `sale_price` decimal(10,4) DEFAULT NULL,
  `stock_actual` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `stock_minimo` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `lote` varchar(255) DEFAULT NULL,
  `fecha_caducidad` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_items_empresa_id_codigo_unique` (`empresa_id`,`codigo`),
  KEY `inventory_items_measurement_unit_id_foreign` (`measurement_unit_id`),
  KEY `inventory_items_account_plan_id_foreign` (`account_plan_id`),
  KEY `inventory_items_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `inventory_items_account_plan_id_foreign` FOREIGN KEY (`account_plan_id`) REFERENCES `account_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_items_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_items_measurement_unit_id_foreign` FOREIGN KEY (`measurement_unit_id`) REFERENCES `measurement_units` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_items_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_items`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `inventory_items` WRITE;
/*!40000 ALTER TABLE `inventory_items` DISABLE KEYS */;
INSERT INTO `inventory_items` VALUES
(2,1,'INV-69B88105C4212','EXTRA',NULL,'materia_prima',NULL,292,2,2.3720,0.0000,7.1480,0.0000,NULL,NULL,1,'2026-03-17 03:15:33','2026-03-17 03:15:33');
/*!40000 ALTER TABLE `inventory_items` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `inventory_movements`
--

DROP TABLE IF EXISTS `inventory_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint(20) unsigned NOT NULL,
  `inventory_item_id` bigint(20) unsigned NOT NULL,
  `type` enum('entrada','salida') NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(14,3) NOT NULL,
  `unit_price` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `total` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `date` date NOT NULL,
  `description` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_movements_empresa_id_foreign` (`empresa_id`),
  KEY `inventory_movements_inventory_item_id_foreign` (`inventory_item_id`),
  KEY `inventory_movements_journal_entry_id_foreign` (`journal_entry_id`),
  CONSTRAINT `inventory_movements_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_movements_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_movements_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_movements`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `inventory_movements` WRITE;
/*!40000 ALTER TABLE `inventory_movements` DISABLE KEYS */;
INSERT INTO `inventory_movements` VALUES
(9,1,2,'entrada','purchase',9,7.148,2.3720,16.9551,'2026-01-01',NULL,'Compra COM-2026-00001',9,'2026-03-17 03:15:33','2026-03-17 03:15:33');
/*!40000 ALTER TABLE `inventory_movements` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_reserved_at_available_at_index` (`queue`,`reserved_at`,`available_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `journal_entries`
--

DROP TABLE IF EXISTS `journal_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `journal_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint(20) unsigned NOT NULL,
  `numero` varchar(255) NOT NULL,
  `fecha` date NOT NULL,
  `descripcion` text NOT NULL,
  `tipo` enum('apertura','manual','compra','venta','manufactura','ajuste','cierre','depreciacion') NOT NULL,
  `origen` enum('manual','automatico') NOT NULL DEFAULT 'manual',
  `referencia_tipo` varchar(255) DEFAULT NULL,
  `referencia_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('borrador','confirmado','anulado') NOT NULL DEFAULT 'borrador',
  `total_debe` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_haber` decimal(14,2) NOT NULL DEFAULT 0.00,
  `esta_cuadrado` tinyint(1) NOT NULL DEFAULT 0,
  `creado_por` bigint(20) unsigned DEFAULT NULL,
  `confirmado_por` bigint(20) unsigned DEFAULT NULL,
  `confirmado_at` timestamp NULL DEFAULT NULL,
  `anulado_por` bigint(20) unsigned DEFAULT NULL,
  `anulado_at` timestamp NULL DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `journal_entries_empresa_id_numero_unique` (`empresa_id`,`numero`),
  KEY `journal_entries_creado_por_foreign` (`creado_por`),
  KEY `journal_entries_confirmado_por_foreign` (`confirmado_por`),
  KEY `journal_entries_anulado_por_foreign` (`anulado_por`),
  CONSTRAINT `journal_entries_anulado_por_foreign` FOREIGN KEY (`anulado_por`) REFERENCES `users` (`id`),
  CONSTRAINT `journal_entries_confirmado_por_foreign` FOREIGN KEY (`confirmado_por`) REFERENCES `users` (`id`),
  CONSTRAINT `journal_entries_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`),
  CONSTRAINT `journal_entries_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal_entries`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `journal_entries` WRITE;
/*!40000 ALTER TABLE `journal_entries` DISABLE KEYS */;
INSERT INTO `journal_entries` VALUES
(9,1,'ASI-2026-00001','2026-01-01','Compra COM-2026-00001 - ATIMASA S.A.','compra','automatico','purchase',9,'confirmado',19.50,19.50,1,1,1,'2026-03-17 03:15:33',NULL,NULL,NULL,'2026-03-17 03:15:33','2026-03-17 03:15:33'),
(11,1,'ASI-2026-00002','2026-01-01','Asiento de apertura - Saldo inicial Banco Produbanco','apertura','manual',NULL,NULL,'confirmado',0.00,0.00,1,NULL,NULL,NULL,NULL,NULL,NULL,'2026-03-17 03:33:35','2026-03-17 03:33:35');
/*!40000 ALTER TABLE `journal_entries` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `journal_entry_lines`
--

DROP TABLE IF EXISTS `journal_entry_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `journal_entry_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `journal_entry_id` bigint(20) unsigned NOT NULL,
  `account_plan_id` bigint(20) unsigned NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `debe` decimal(14,2) NOT NULL DEFAULT 0.00,
  `haber` decimal(14,2) NOT NULL DEFAULT 0.00,
  `orden` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `journal_entry_lines_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `journal_entry_lines_account_plan_id_foreign` (`account_plan_id`),
  CONSTRAINT `journal_entry_lines_account_plan_id_foreign` FOREIGN KEY (`account_plan_id`) REFERENCES `account_plans` (`id`),
  CONSTRAINT `journal_entry_lines_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal_entry_lines`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `journal_entry_lines` WRITE;
/*!40000 ALTER TABLE `journal_entry_lines` DISABLE KEYS */;
INSERT INTO `journal_entry_lines` VALUES
(25,9,292,'EXTRA',16.96,0.00,1,'2026-03-17 03:15:33','2026-03-17 03:15:33'),
(26,9,192,'IVA 15% - EXTRA',2.54,0.00,2,'2026-03-17 03:15:33','2026-03-17 03:15:33'),
(27,9,179,'Pago compra COM-2026-00001 (Efectivo)',0.00,19.50,3,'2026-03-17 03:15:33','2026-03-17 03:15:33'),
(30,11,181,'Saldo inicial cuenta bancaria Produbanco',5000.00,0.00,0,'2026-03-17 03:33:35','2026-03-17 03:33:35'),
(31,11,238,'Capital suscrito y pagado - apertura',0.00,5000.00,0,'2026-03-17 03:33:35','2026-03-17 03:33:35');
/*!40000 ALTER TABLE `journal_entry_lines` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `measurement_units`
--

DROP TABLE IF EXISTS `measurement_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `measurement_units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint(20) unsigned NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `abreviatura` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `measurement_units_empresa_id_foreign` (`empresa_id`),
  CONSTRAINT `measurement_units_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `measurement_units`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `measurement_units` WRITE;
/*!40000 ALTER TABLE `measurement_units` DISABLE KEYS */;
/*!40000 ALTER TABLE `measurement_units` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2026_03_11_215119_create_companies_table',1),
(5,'2026_03_11_220704_create_permission_tables',1),
(6,'2026_03_11_224513_add_module_fields_to_companies_table',1),
(7,'2026_03_11_224513_create_account_plans_table',1),
(8,'2026_03_11_232019_create_suppliers_table',1),
(9,'2026_03_11_233405_create_measurement_units_table',1),
(10,'2026_03_11_233406_create_inventory_items_table',1),
(11,'2026_03_11_233407_create_inventory_item_files_table',1),
(12,'2026_03_12_001839_create_accounting_maps_table',1),
(13,'2026_03_12_004648_create_journal_entries_table',1),
(14,'2026_03_12_004649_create_journal_entry_lines_table',1),
(15,'2026_03_12_010835_create_purchases_table',1),
(16,'2026_03_12_010836_create_inventory_movements_table',1),
(17,'2026_03_12_010836_create_purchase_items_table',1),
(18,'2026_03_12_023425_rename_companies_to_empresas_table',1),
(19,'2026_03_12_023427_rename_proveedores_to_suppliers_table',1),
(20,'2026_03_12_023429_rename_company_id_to_empresa_id_in_account_plans',1),
(21,'2026_03_12_023430_rename_company_id_to_empresa_id_in_accounting_maps',1),
(22,'2026_03_12_023432_rename_company_id_to_empresa_id_in_inventory_items',1),
(23,'2026_03_12_023435_rename_company_id_to_empresa_id_in_inventory_movements',1),
(24,'2026_03_12_023438_rename_company_id_to_empresa_id_in_journal_entries',1),
(25,'2026_03_12_023440_rename_company_id_to_empresa_id_in_purchases',1),
(26,'2026_03_12_023442_rename_company_id_to_empresa_id_in_measurement_units',1),
(27,'2026_03_12_023447_rename_company_id_to_empresa_id_in_suppliers',1),
(28,'2026_03_12_023632_rename_price_to_unit_price_in_purchase_items',1),
(29,'2026_03_12_031017_add_empresa_id_to_users_table',1),
(30,'2026_03_12_032249_add_slug_and_activo_to_empresas_table',1),
(31,'2026_03_12_035853_add_ajuste_sobrante_to_accounting_maps_enum',1),
(32,'2026_03_12_041059_update_purchases_and_items_tables_v2',1),
(33,'2026_03_12_044500_fix_total_item_column_in_purchase_items',1),
(34,'2026_03_12_045000_add_missing_columns_to_inventory_movements',1),
(35,'2026_03_13_031710_create_sales_module_tables',1),
(36,'2026_03_13_040000_create_manufacturing_module_tables',1),
(37,'2026_03_13_081500_create_banks_table',1),
(38,'2026_03_13_081501_create_bank_accounts_table',1),
(39,'2026_03_13_081502_add_payment_fields_to_sales_table',1),
(40,'2026_03_13_134338_update_tipo_item_enum_in_sale_items',1),
(41,'2026_03_13_135942_create_cash_registers_table',1),
(42,'2026_03_13_135943_create_cash_sessions_table',1),
(43,'2026_03_13_135944_create_credit_cards_table',1),
(44,'2026_03_13_135945_create_credit_card_movements_table',1),
(45,'2026_03_13_135946_add_treasury_fields_to_purchases_table',1),
(46,'2026_03_13_135947_add_treasury_fields_to_sales_table',1),
(47,'2026_03_13_135948_create_cash_movements_table',1),
(48,'2026_03_16_000001_add_identification_fields_to_empresas_table',2),
(50,'2026_03_16_100000_fix_missing_accounting_maps',3),
(51,'2026_03_16_213930_sync_all_accounting_maps_to_empresas',4);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES
(1,'App\\Models\\User',1);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES
(1,'reportes.ver','web','2026-03-16 23:25:18','2026-03-16 23:25:18'),
(2,'reportes.exportar','web','2026-03-16 23:25:18','2026-03-16 23:25:18'),
(3,'proveedores.ver','web','2026-03-16 23:25:18','2026-03-16 23:25:18'),
(4,'proveedores.crear','web','2026-03-16 23:25:18','2026-03-16 23:25:18'),
(5,'proveedores.editar','web','2026-03-16 23:25:18','2026-03-16 23:25:18'),
(6,'proveedores.eliminar','web','2026-03-16 23:25:18','2026-03-16 23:25:18'),
(7,'inventario.ver','web','2026-03-16 23:25:18','2026-03-16 23:25:18'),
(8,'inventario.crear','web','2026-03-16 23:25:18','2026-03-16 23:25:18'),
(9,'inventario.editar','web','2026-03-16 23:25:18','2026-03-16 23:25:18'),
(10,'inventario.eliminar','web','2026-03-16 23:25:18','2026-03-16 23:25:18');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `production_materials`
--

DROP TABLE IF EXISTS `production_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `production_materials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `production_order_id` bigint(20) unsigned NOT NULL,
  `inventory_item_id` bigint(20) unsigned NOT NULL,
  `cantidad_consumida` decimal(12,4) NOT NULL,
  `costo_unitario` decimal(14,4) NOT NULL,
  `costo_total` decimal(14,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `production_materials_production_order_id_foreign` (`production_order_id`),
  KEY `production_materials_inventory_item_id_foreign` (`inventory_item_id`),
  CONSTRAINT `production_materials_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`),
  CONSTRAINT `production_materials_production_order_id_foreign` FOREIGN KEY (`production_order_id`) REFERENCES `production_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_materials`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `production_materials` WRITE;
/*!40000 ALTER TABLE `production_materials` DISABLE KEYS */;
/*!40000 ALTER TABLE `production_materials` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `production_orders`
--

DROP TABLE IF EXISTS `production_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `production_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint(20) unsigned NOT NULL,
  `referencia` varchar(255) NOT NULL,
  `fecha` date NOT NULL,
  `inventory_item_id` bigint(20) unsigned NOT NULL,
  `cantidad_producida` decimal(12,4) NOT NULL,
  `costo_total` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `estado` enum('borrador','completado','anulado') NOT NULL DEFAULT 'borrador',
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `completado_por` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `production_orders_empresa_id_referencia_unique` (`empresa_id`,`referencia`),
  KEY `production_orders_inventory_item_id_foreign` (`inventory_item_id`),
  KEY `production_orders_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `production_orders_completado_por_foreign` (`completado_por`),
  CONSTRAINT `production_orders_completado_por_foreign` FOREIGN KEY (`completado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_orders_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `production_orders_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`),
  CONSTRAINT `production_orders_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_orders`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `production_orders` WRITE;
/*!40000 ALTER TABLE `production_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `production_orders` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `purchase_items`
--

DROP TABLE IF EXISTS `purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint(20) unsigned NOT NULL,
  `inventory_item_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(14,3) NOT NULL,
  `unit_price` decimal(14,4) NOT NULL,
  `aplica_iva` tinyint(1) NOT NULL DEFAULT 1,
  `subtotal` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `iva_monto` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `total_item` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_items_purchase_id_foreign` (`purchase_id`),
  KEY `purchase_items_inventory_item_id_foreign` (`inventory_item_id`),
  CONSTRAINT `purchase_items_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_items`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `purchase_items` WRITE;
/*!40000 ALTER TABLE `purchase_items` DISABLE KEYS */;
INSERT INTO `purchase_items` VALUES
(9,9,2,7.148,2.3720,1,16.9551,2.5433,19.50,'2026-03-17 03:15:33','2026-03-17 03:15:33');
/*!40000 ALTER TABLE `purchase_items` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `purchases`
--

DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `number` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notas` text DEFAULT NULL,
  `status` enum('borrador','confirmado','anulado') NOT NULL DEFAULT 'borrador',
  `tipo_pago` enum('contado','credito_local','credito_exterior') NOT NULL DEFAULT 'contado',
  `fecha_vencimiento` date DEFAULT NULL,
  `subtotal` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `iva` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `confirmado_por` bigint(20) unsigned DEFAULT NULL,
  `confirmado_at` timestamp NULL DEFAULT NULL,
  `forma_pago` enum('efectivo','transferencia','cheque','tarjeta','credito') NOT NULL DEFAULT 'efectivo',
  `cash_register_id` bigint(20) unsigned DEFAULT NULL,
  `credit_card_id` bigint(20) unsigned DEFAULT NULL,
  `bank_account_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchases_empresa_id_number_unique` (`empresa_id`,`number`),
  KEY `purchases_supplier_id_foreign` (`supplier_id`),
  KEY `purchases_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `purchases_confirmado_por_foreign` (`confirmado_por`),
  KEY `purchases_cash_register_id_foreign` (`cash_register_id`),
  KEY `purchases_credit_card_id_foreign` (`credit_card_id`),
  KEY `purchases_bank_account_id_foreign` (`bank_account_id`),
  CONSTRAINT `purchases_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchases_cash_register_id_foreign` FOREIGN KEY (`cash_register_id`) REFERENCES `cash_registers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchases_confirmado_por_foreign` FOREIGN KEY (`confirmado_por`) REFERENCES `users` (`id`),
  CONSTRAINT `purchases_credit_card_id_foreign` FOREIGN KEY (`credit_card_id`) REFERENCES `credit_cards` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchases_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchases_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchases_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchases`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `purchases` WRITE;
/*!40000 ALTER TABLE `purchases` DISABLE KEYS */;
INSERT INTO `purchases` VALUES
(9,1,2,'COM-2026-00001','2026-01-01',19.50,'Importado desde Factura SRI: 198-053-000175514','confirmado','contado',NULL,16.9551,2.5433,9,'2026-03-17 03:15:33','2026-03-17 03:15:33',1,'2026-03-17 03:15:33','efectivo',NULL,NULL,NULL);
/*!40000 ALTER TABLE `purchases` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES
(1,2),
(2,2),
(3,2),
(4,2),
(5,2),
(6,2),
(7,2),
(8,2),
(9,2),
(10,2),
(1,3),
(2,3),
(3,3),
(7,3),
(3,4),
(7,4),
(8,4),
(9,4);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES
(1,'super_admin','web','2026-03-16 23:25:18','2026-03-16 23:25:18'),
(2,'admin_empresa','web','2026-03-16 23:25:18','2026-03-16 23:25:18'),
(3,'contador','web','2026-03-16 23:25:18','2026-03-16 23:25:18'),
(4,'inventario','web','2026-03-16 23:25:18','2026-03-16 23:25:18');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `sale_items`
--

DROP TABLE IF EXISTS `sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned NOT NULL,
  `inventory_item_id` bigint(20) unsigned DEFAULT NULL,
  `descripcion_servicio` varchar(255) DEFAULT NULL,
  `tipo_item` enum('producto_terminado','materia_prima','insumo','servicio','activo_fijo_maquinaria','activo_fijo_computo','activo_fijo_vehiculo','activo_fijo_muebles') NOT NULL DEFAULT 'producto_terminado',
  `cantidad` decimal(10,4) NOT NULL,
  `precio_unitario` decimal(14,4) NOT NULL,
  `aplica_iva` tinyint(1) NOT NULL DEFAULT 1,
  `subtotal` decimal(14,2) NOT NULL,
  `iva_monto` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_items_sale_id_foreign` (`sale_id`),
  KEY `sale_items_inventory_item_id_foreign` (`inventory_item_id`),
  CONSTRAINT `sale_items_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`),
  CONSTRAINT `sale_items_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_items`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `sale_items` WRITE;
/*!40000 ALTER TABLE `sale_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_items` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint(20) unsigned NOT NULL,
  `referencia` varchar(255) NOT NULL,
  `fecha` date NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `tipo_venta` enum('contado','credito') NOT NULL DEFAULT 'contado',
  `forma_pago` enum('efectivo','transferencia','cheque','tarjeta','credito') NOT NULL DEFAULT 'efectivo',
  `bank_account_id` bigint(20) unsigned DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `tipo_operacion` enum('productos','servicios','mixta','exportacion') NOT NULL DEFAULT 'productos',
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `iva` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `estado` enum('borrador','confirmado','anulado') NOT NULL DEFAULT 'borrador',
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `confirmado_por` bigint(20) unsigned DEFAULT NULL,
  `confirmado_at` timestamp NULL DEFAULT NULL,
  `factura_electronica_id` varchar(255) DEFAULT NULL,
  `clave_acceso` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cash_register_id` bigint(20) unsigned DEFAULT NULL,
  `credit_card_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_empresa_id_referencia_unique` (`empresa_id`,`referencia`),
  KEY `sales_customer_id_foreign` (`customer_id`),
  KEY `sales_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `sales_confirmado_por_foreign` (`confirmado_por`),
  KEY `sales_referencia_index` (`referencia`),
  KEY `sales_bank_account_id_foreign` (`bank_account_id`),
  KEY `sales_cash_register_id_foreign` (`cash_register_id`),
  KEY `sales_credit_card_id_foreign` (`credit_card_id`),
  CONSTRAINT `sales_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_cash_register_id_foreign` FOREIGN KEY (`cash_register_id`) REFERENCES `cash_registers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_confirmado_por_foreign` FOREIGN KEY (`confirmado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_credit_card_id_foreign` FOREIGN KEY (`credit_card_id`) REFERENCES `credit_cards` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `sales_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES
('FIaR02FAvy9wOzGiZV3TWV7oDAcQoQyWNbv3Ckey',1,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','YTo2OntzOjY6Il90b2tlbiI7czo0MDoiTzBZQmVHV3ByR2RYRnRsR2lkcGMwN1MxQ1EyaXRwYmk5bWlQNXR2TyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hcHAvcml2ZXQtZWN1YWRvci1zYXMvZXN0YWRvLXJlc3VsdGFkb3MiO3M6NToicm91dGUiO3M6MzY6ImZpbGFtZW50LmFwcC5wYWdlcy5lc3RhZG8tcmVzdWx0YWRvcyI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjQ6IjFmYzQ1YWI2Y2IzOGM4NjRhZjhjODA3MDcwYTZiZjNjMThlNjA2M2FjOTU0ZTEyODc4NGRkMmJhMDgwZmEyNzEiO3M6ODoiZmlsYW1lbnQiO2E6MDp7fX0=',1773703765);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint(20) unsigned NOT NULL,
  `codigo` varchar(255) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `nombre_comercial` varchar(255) DEFAULT NULL,
  `tipo_persona` enum('juridica','natural') NOT NULL,
  `tipo_identificacion` enum('ruc','cedula','pasaporte') NOT NULL,
  `numero_identificacion` varchar(255) NOT NULL,
  `tipo_proveedor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`tipo_proveedor`)),
  `contacto_principal` varchar(255) NOT NULL,
  `telefono_principal` varchar(255) NOT NULL,
  `telefono_secundario` varchar(255) DEFAULT NULL,
  `correo_principal` varchar(255) NOT NULL,
  `correo_secundario` varchar(255) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `ciudad` varchar(255) DEFAULT NULL,
  `pais` varchar(255) NOT NULL DEFAULT 'Ecuador',
  `es_importador` tinyint(1) NOT NULL DEFAULT 0,
  `pais_origen` varchar(255) DEFAULT NULL,
  `cuenta_contable_id` bigint(20) unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proveedores_empresa_id_codigo_unique` (`empresa_id`,`codigo`),
  KEY `proveedores_cuenta_contable_id_foreign` (`cuenta_contable_id`),
  CONSTRAINT `proveedores_cuenta_contable_id_foreign` FOREIGN KEY (`cuenta_contable_id`) REFERENCES `account_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `proveedores_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES
(2,1,'PRV-69B88105C18CF','ATIMASA S.A.',NULL,'juridica','ruc','0991331859001','[\"insumos\"]','ATIMASA S.A.','0000000000',NULL,'empresa@rivet-ec.com',NULL,'AV. DE LAS AMERICAS 406-GUAYAQUIL',NULL,'Ecuador',0,NULL,NULL,1,'2026-03-17 03:15:33','2026-03-17 03:15:33');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `empresa_id` bigint(20) unsigned DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_empresa_id_foreign` (`empresa_id`),
  CONSTRAINT `users_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Admin Mashaec','admin@mashaec.net',NULL,NULL,'$2y$12$vRriTijHMnqNNqe.NCyJU.6wc61GTt36V04uUzHLTYlxoE8el2tNa',NULL,'2026-03-16 23:25:18','2026-03-16 23:25:18');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-03-16 18:31:38
