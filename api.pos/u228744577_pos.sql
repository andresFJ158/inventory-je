-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 24-11-2025 a las 12:43:10
-- Versión del servidor: 11.8.3-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u228744577_pos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admins`
--

CREATE TABLE `admins` (
  `id_admin` int(11) NOT NULL,
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
  `date_updated_admin` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `admins`
--

INSERT INTO `admins` (`id_admin`, `email_admin`, `password_admin`, `rol_admin`, `permissions_admin`, `token_admin`, `token_exp_admin`, `status_admin`, `title_admin`, `symbol_admin`, `font_admin`, `color_admin`, `back_admin`, `scode_admin`, `name_admin`, `id_office_admin`, `chatgpt_admin`, `date_created_admin`, `date_updated_admin`) VALUES
(1, 'superadmin@pos.com', '$2a$07$azybxcags23425sdg23sdeanQZqjaf6Birm2NvcYTNtJw24CsO5uq', 'superadmin', '{\"todo\":\"on\"}', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NjI1NzY2NTksImV4cCI6MTc2MjY2MzA1OSwiZGF0YSI6eyJpZCI6MSwiZW1haWwiOiJzdXBlcmFkbWluQHBvcy5jb20ifX0.vcz7mqyXQLnohiRAtUEmTZIY3JfLmBZKp6sMetioOKs', '1762663059', 1, 'POS SYSTEM', '<i class=\"bi bi-cart-check-fill\"></i>', '<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\"><link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin><link href=\"https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap\" rel=\"stylesheet\">', '#611be4', 'http://cms.pos.com/views/assets/files/6760a08e6d34e6.png', 'w958zu', 'El Programador', 0, '{\"token\":\"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\",\"org\":\"\"}', '2024-12-16', '2025-11-08 04:37:39'),
(13, 'marisol@jebolivia.com.bo', '$2a$07$azybxcags23425sdg23sdeanQZqjaf6Birm2NvcYTNtJw24CsO5uq', 'admin', '%7B%22archivos%22%3A%22%22%2C%22clientes%22%3A%22%22%2C%22categorias%22%3A%22%22%2C%22productos%22%3A%22%22%2C%22compras%22%3A%22%22%2C%22ordenes%22%3A%22%22%2C%22gastos%22%3A%22%22%2C%22informes%22%3A%22%22%7D', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NjI1NDg5NjAsImV4cCI6MTc2MjYzNTM2MCwiZGF0YSI6eyJpZCI6MTMsImVtYWlsIjoibWFyaXNvbEBqZWJvbGl2aWEuY29tLmJvIn19.x1GFb_wQYg1QYX_j4g0o61IwHbAD8G18mCZNEvW_FCE', '1762635360', 1, '', '', '', '', '', '', 'Marisol+Silva', 3, NULL, '2025-10-25', '2025-11-07 20:56:00'),
(14, 'caja@pos.com', '', 'editor', '{\"pos\":\"on\",\"clientes\":\"on\",\"ordenes\":\"on\",\"caja\":\"on\",\"gastos\":\"on\"}', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NjI1Nzc3NzEsImV4cCI6MTc2MjY2NDE3MSwiZGF0YSI6eyJpZCI6MTQsImVtYWlsIjoiY2FqYUBwb3MuY29tIn19.KvGwZzLgouwElfWcO2hgfJJp1_ne7Bi7lyLHXxpkR_k', '1762664171', 1, '', '', '', '', '', '', 'Charito - Caja', 3, NULL, '2025-10-25', '2025-11-08 04:56:11'),
(15, 'admin@pos.com', '$2a$07$azybxcags23425sdg23sdeanQZqjaf6Birm2NvcYTNtJw24CsO5uq', 'admin', '%7B%7D', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NjI5Njk2ODQsImV4cCI6MTc2MzA1NjA4NCwiZGF0YSI6eyJpZCI6MTUsImVtYWlsIjoiYWRtaW5AcG9zLmNvbSJ9fQ.xsMFn7u5vd0Tu_KtfpAu9dZ-I9L32lB0vmjwhRVxZRI', '1763056084', 1, '', '', '', '', '', '', 'Admin', 3, NULL, '2025-11-08', '2025-11-12 17:48:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bills`
--

CREATE TABLE `bills` (
  `id_bill` int(11) NOT NULL,
  `concept_bill` text DEFAULT NULL,
  `cost_bill` double DEFAULT 0,
  `date_bill` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_cash_bill` int(11) DEFAULT 0,
  `id_admin_bill` int(11) DEFAULT 0,
  `id_office_bill` int(11) DEFAULT 0,
  `date_created_bill` date DEFAULT NULL,
  `date_updated_bill` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incomes`
--

CREATE TABLE `incomes` (
  `id_income` int(11) NOT NULL,
  `concept_income` text DEFAULT NULL,
  `amount_income` double DEFAULT 0,
  `date_income` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_cash_income` int(11) DEFAULT 0,
  `id_admin_income` int(11) DEFAULT 0,
  `id_office_income` int(11) DEFAULT 0,
  `date_created_income` date DEFAULT NULL,
  `date_updated_income` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `bills`
--

INSERT INTO `bills` (`id_bill`, `concept_bill`, `cost_bill`, `date_bill`, `id_admin_bill`, `id_office_bill`, `date_created_bill`, `date_updated_bill`) VALUES
(4, 'Fotocopias', 10, '2024-12-28 01:03:00', 1, 3, '2024-12-27', '2025-11-04 05:11:27'),
(5, 'Cinta', 10, '2025-10-22 13:03:00', 1, 3, '2025-10-22', '2025-11-04 05:11:07'),
(7, 'Fotocopias', 5.5, '2025-11-07 17:03:00', 13, 3, '2025-11-07', '2025-11-07 21:19:01'),
(8, 'Fotocopias', 3.5, '2025-11-08 00:03:00', 14, 3, '2025-11-08', '2025-11-08 04:53:39'),
(9, 'Cinta', 10, '2025-11-08 00:03:00', 1, 3, '2025-11-08', '2025-11-08 04:59:53'),
(10, 'Fotocopia', 20, '2025-11-08 01:03:00', 1, 3, '2025-11-08', '2025-11-08 05:15:17'),
(11, 'Prueba', 5, '2025-11-08 01:03:00', 1, 3, '2025-11-08', '2025-11-08 05:16:56'),
(12, 'Prueba', 5, '2025-11-08 01:03:00', 1, 3, '2025-11-08', '2025-11-08 05:26:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cashs`
--

CREATE TABLE `cashs` (
  `id_cash` int(11) NOT NULL,
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
  `date_updated_cash` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `cashs`
--

INSERT INTO `cashs` (`id_cash`, `start_cash`, `bills_cash`, `money_cash`, `diff_cash`, `end_cash`, `gap_cash`, `status_cash`, `date_start_cash`, `date_end_cash`, `id_admin_cash`, `id_office_cash`, `date_created_cash`, `date_updated_cash`) VALUES
(8, 250, 0, 1295, 1545, 1545, 0, 0, '2025-10-31 19:03:00', '2025-10-31 22:03:00', 1, 3, '2025-10-31', '2025-11-01 02:08:04'),
(9, 350, 0, 155, 505, 505, 0, 0, '2025-11-03 23:03:00', '2025-11-04 00:03:00', 13, 3, '2025-11-03', '2025-11-04 04:09:18'),
(10, 315, 0, 0, 315, 315, 0, 0, '2025-11-04 00:03:00', '2025-11-04 01:03:00', 1, 3, '2025-11-04', '2025-11-04 05:08:26'),
(15, 674.5, 10, 0, 0, 0, 0, 0, '2025-11-04 15:03:00', '2025-11-04 17:03:00', 13, 3, '2025-11-04', '2025-11-07 20:38:33'),
(16, 651.5, 20, 0, 0, 0, 0, 0, '2025-11-07 17:03:00', '2025-11-07 19:03:00', 13, 3, '2025-11-07', '2025-11-07 21:05:46'),
(17, 312.5, 0, 0, 312.5, 312.5, 0, 0, '2025-11-07 17:03:00', '2025-11-07 21:03:00', 13, 3, '2025-11-07', '2025-11-08 04:38:57'),
(18, 450, 0, 245, 695, 695, 0, 0, '2025-11-08 00:03:00', '2025-11-08 02:03:00', 14, 3, '2025-11-08', '2025-11-08 04:45:56'),
(19, 351.5, -3.5, 340, 688, 688, 0, 0, '2025-11-08 00:03:00', '2025-11-08 04:03:00', 14, 3, '2025-11-08', '2025-11-08 04:59:05'),
(21, 150, 10, 0, 0, 0, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 1, 0, '2025-11-08', '2025-11-08 05:16:20'),
(22, 500, 10, 0, 0, 0, 0, 1, '2025-11-08 01:03:00', '2025-11-08 02:03:00', 1, 3, '2025-11-08', '2025-11-08 05:26:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `id_category` int(11) NOT NULL,
  `title_category` text DEFAULT NULL,
  `img_category` text DEFAULT NULL,
  `order_category` int(11) DEFAULT 0,
  `status_category` int(11) DEFAULT 1,
  `date_created_category` date DEFAULT NULL,
  `date_updated_category` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `categories`
--

INSERT INTO `categories` (`id_category`, `title_category`, `img_category`, `order_category`, `status_category`, `date_created_category`, `date_updated_category`) VALUES
(8, 'Vinagres', 'https%3A%2F%2Fpos.desarrolloweb24siete.com%2F%2Fviews%2Fassets%2Ffiles%2F69025436db56e50.png', 0, 1, '2025-10-29', '2025-10-29 19:44:39'),
(9, 'Tonicos', 'https://pos.desarrolloweb24siete.com//views/assets/files/69025436db56e50.png', 0, 1, '2025-10-29', '2025-10-29 19:45:27'),
(10, 'Vital+Herbs', '', 0, 1, '2025-10-29', '2025-10-29 20:39:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clients`
--

CREATE TABLE `clients` (
  `id_client` int(11) NOT NULL,
  `dni_client` text DEFAULT NULL,
  `name_client` text DEFAULT NULL,
  `surname_client` text DEFAULT NULL,
  `email_client` text DEFAULT NULL,
  `address_client` text DEFAULT NULL,
  `phone_client` text DEFAULT NULL,
  `id_office_client` int(11) DEFAULT 0,
  `date_created_client` date DEFAULT NULL,
  `date_updated_client` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `clients`
--

INSERT INTO `clients` (`id_client`, `dni_client`, `name_client`, `surname_client`, `email_client`, `address_client`, `phone_client`, `id_office_client`, `date_created_client`, `date_updated_client`) VALUES
(14, '13334410', 'Andres', 'Fernandez', 'andres@gmail.com', 'La+Pradera', '60836039', 3, '2025-10-25', '2025-10-25 23:10:04'),
(15, '2433154', 'Norma', 'Jaldin', 'norma@gmail.com', 'La Pradera', '79005900', 3, '2025-10-25', '2025-10-25 23:16:57'),
(16, '2324033', 'Federico Enrique', 'Fernandez Pabon', 'federico@gmail.com', 'Condominio La Pradera zona norte', '70837024', 4, '2025-10-30', '2025-10-31 03:26:37'),
(18, '6625123', 'Daniel', 'Fernandez', 'daniel@gmail.com', 'Sevilla+Terrazas', '79555012', 3, '2025-11-08', '2025-11-08 04:49:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `columns`
--

CREATE TABLE `columns` (
  `id_column` int(11) NOT NULL,
  `id_module_column` int(11) DEFAULT 0,
  `title_column` text DEFAULT NULL,
  `alias_column` text DEFAULT NULL,
  `type_column` text DEFAULT NULL,
  `matrix_column` text DEFAULT NULL,
  `visible_column` int(11) DEFAULT 1,
  `date_created_column` date DEFAULT NULL,
  `date_updated_column` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `columns`
--

INSERT INTO `columns` (`id_column`, `id_module_column`, `title_column`, `alias_column`, `type_column`, `matrix_column`, `visible_column`, `date_created_column`, `date_updated_column`) VALUES
(1, 2, 'rol_admin', 'rol', 'select', 'superadmin,admin,editor', 1, '2024-12-16', '2025-10-26 00:02:08'),
(2, 2, 'permissions_admin', 'permisos', 'object', '', 1, '2024-12-16', '2024-12-16 21:46:24'),
(3, 2, 'email_admin', 'email', 'email', '', 1, '2024-12-16', '2024-12-16 21:46:24'),
(4, 2, 'password_admin', 'pass', 'password', '', 0, '2024-12-16', '2024-12-16 21:46:24'),
(5, 2, 'token_admin', 'token', 'text', '', 0, '2024-12-16', '2024-12-16 21:46:24'),
(6, 2, 'token_exp_admin', 'expiración', 'text', '', 0, '2024-12-16', '2024-12-16 21:46:24'),
(7, 2, 'status_admin', 'estado', 'boolean', '', 1, '2024-12-16', '2024-12-16 21:46:24'),
(8, 2, 'title_admin', 'título', 'text', '', 0, '2024-12-16', '2024-12-16 21:46:25'),
(9, 2, 'symbol_admin', 'simbolo', 'text', '', 0, '2024-12-16', '2024-12-16 21:46:25'),
(10, 2, 'font_admin', 'tipografía', 'text', '', 0, '2024-12-16', '2024-12-16 21:46:25'),
(11, 2, 'color_admin', 'color', 'text', '', 0, '2024-12-16', '2024-12-16 21:46:25'),
(12, 2, 'back_admin', 'fondo', 'text', '', 0, '2024-12-16', '2024-12-16 21:46:25'),
(13, 2, 'scode_admin', 'seguridad', 'text', '', 0, '2024-12-16', '2024-12-16 21:46:25'),
(14, 4, 'title_office', 'Sucursales', 'text', NULL, 1, '2024-12-17', '2024-12-16 23:17:24'),
(15, 4, 'address_office', 'Dirección', 'text', NULL, 1, '2024-12-17', '2024-12-16 23:17:24'),
(16, 4, 'phone_office', 'Teléfono', 'text', NULL, 1, '2024-12-17', '2024-12-16 23:17:24'),
(17, 6, 'dni_client', 'Documento', 'text', NULL, 1, '2024-12-18', '2024-12-18 19:37:40'),
(18, 6, 'name_client', 'Nombre', 'text', NULL, 1, '2024-12-18', '2024-12-18 19:37:40'),
(19, 6, 'surname_client', 'Apellido', 'text', NULL, 1, '2024-12-18', '2024-12-18 19:37:40'),
(20, 6, 'email_client', 'Email', 'email', NULL, 1, '2024-12-18', '2024-12-18 19:37:40'),
(21, 6, 'address_client', 'Dirección', 'text', NULL, 1, '2024-12-18', '2024-12-18 19:37:41'),
(22, 6, 'phone_client', 'Teléfono', 'text', NULL, 1, '2024-12-18', '2024-12-18 19:37:41'),
(23, 6, 'id_office_client', 'Sucursal', 'relations', 'offices', 1, '2024-12-18', '2024-12-18 19:38:33'),
(24, 8, 'title_category', 'Categoría', 'text', NULL, 1, '2024-12-18', '2024-12-18 20:14:59'),
(25, 8, 'img_category', 'Imagen', 'image', NULL, 1, '2024-12-18', '2024-12-18 20:15:00'),
(26, 8, 'order_category', 'Orden', 'order', NULL, 1, '2024-12-18', '2024-12-18 20:15:00'),
(27, 8, 'status_category', 'Estado', 'boolean', NULL, 1, '2024-12-18', '2024-12-18 20:15:00'),
(28, 10, 'title_product', 'Producto', 'text', NULL, 1, '2024-12-18', '2024-12-18 20:38:31'),
(29, 10, 'img_product', 'Imagen', 'image', NULL, 1, '2024-12-18', '2024-12-18 20:38:31'),
(30, 10, 'id_category_product', 'Categoría', 'relations', 'categories', 1, '2024-12-18', '2024-12-18 20:42:20'),
(31, 10, 'sku_product', 'SKU', 'text', NULL, 1, '2024-12-18', '2024-12-18 20:38:32'),
(32, 10, 'unit_product', 'Medida', 'select', '1Lt,2Lt,500gr,50gr', 1, '2024-12-18', '2025-11-08 04:51:03'),
(35, 10, 'stock_product', 'Stock', 'text', NULL, 1, '2024-12-18', '2025-10-29 22:14:18'),
(36, 10, 'discount_product', 'Descuento en %', 'double', NULL, 1, '2024-12-18', '2025-10-29 19:49:54'),
(37, 10, 'status_product', 'Estado', 'boolean', NULL, 1, '2024-12-18', '2024-12-18 20:38:33'),
(38, 10, 'id_office_product', 'Sucursal', 'relations', 'offices', 1, '2024-12-18', '2025-10-31 22:18:15'),
(48, 14, 'transaction_order', 'Transacción', 'pos', NULL, 1, '2024-12-18', '2024-12-28 00:49:38'),
(49, 14, 'id_admin_order', 'Vendedor', 'relations', 'admins', 1, '2024-12-18', '2024-12-18 22:41:54'),
(50, 14, 'id_client_order', 'Cliente', 'relations', 'clients', 1, '2024-12-18', '2024-12-18 22:42:03'),
(51, 14, 'subtotal_order', 'Subtotal', 'money', NULL, 1, '2024-12-18', '2024-12-18 22:41:11'),
(52, 14, 'discount_order', 'Descuento', 'money', NULL, 1, '2024-12-18', '2024-12-18 22:41:11'),
(53, 14, 'tax_order', 'Impuesto', 'money', NULL, 1, '2024-12-18', '2024-12-18 22:41:12'),
(54, 14, 'total_order', 'Total', 'money', NULL, 1, '2024-12-18', '2024-12-18 22:41:12'),
(55, 14, 'method_order', 'Método', 'select', 'efectivo,transferencia,tarjeta', 1, '2024-12-18', '2024-12-18 22:46:09'),
(56, 14, 'transfer_order', 'Transferencia', 'text', NULL, 1, '2024-12-18', '2024-12-18 22:41:12'),
(57, 14, 'status_order', 'Estado', 'select', 'Completada,Pendiente', 1, '2024-12-18', '2024-12-18 22:46:26'),
(58, 14, 'date_order', 'Fecha', 'timestamp', NULL, 1, '2024-12-18', '2024-12-18 22:41:13'),
(59, 14, 'id_office_order', 'Sucursal', 'relations', 'offices', 1, '2024-12-18', '2024-12-18 22:42:12'),
(60, 16, 'id_order_sale', 'Orden', 'relations', 'orders', 1, '2024-12-18', '2024-12-18 22:55:22'),
(61, 16, 'id_product_sale', 'Producto', 'relations', 'products', 1, '2024-12-18', '2024-12-18 22:55:18'),
(62, 16, 'tax_type_sale', 'Tipo Impuesto', 'text', NULL, 1, '2024-12-18', '2024-12-18 22:54:25'),
(63, 16, 'tax_sale', 'Impuesto', 'double', NULL, 1, '2024-12-18', '2025-02-15 00:13:12'),
(64, 16, 'discount_sale', 'Descuento', 'double', NULL, 1, '2024-12-18', '2025-02-15 00:13:12'),
(65, 16, 'qty_sale', 'Cantidad', 'int', NULL, 1, '2024-12-18', '2024-12-18 22:54:25'),
(66, 16, 'subtotal_sale', 'Subtotal', 'money', NULL, 1, '2024-12-18', '2024-12-18 22:54:26'),
(67, 16, 'status_sale', 'Estado', 'select', 'Completada,Pendiente', 1, '2024-12-18', '2024-12-18 22:55:10'),
(68, 16, 'id_admin_sale', 'Vendedor', 'relations', 'admins', 1, '2024-12-18', '2024-12-18 22:55:01'),
(69, 16, 'id_client_sale', 'Cliente', 'relations', 'clients', 1, '2024-12-18', '2024-12-18 22:54:56'),
(70, 16, 'id_office_sale', 'Sucursal', 'relations', 'offices', 1, '2024-12-18', '2024-12-18 22:54:49'),
(71, 18, 'start_cash', 'Dinero Inicial', 'money', NULL, 1, '2024-12-19', '2024-12-18 23:09:25'),
(72, 18, 'bills_cash', 'Gastos', 'money', NULL, 1, '2024-12-19', '2024-12-18 23:09:26'),
(73, 18, 'money_cash', 'Ingresos', 'money', NULL, 1, '2024-12-19', '2024-12-18 23:09:26'),
(74, 18, 'diff_cash', 'Diferencia', 'money', NULL, 1, '2024-12-19', '2024-12-18 23:09:26'),
(75, 18, 'end_cash', 'Dinero Final', 'money', NULL, 1, '2024-12-19', '2024-12-18 23:09:26'),
(76, 18, 'gap_cash', 'Brecha', 'money', NULL, 1, '2024-12-19', '2024-12-18 23:09:27'),
(77, 18, 'status_cash', 'Estado', 'boolean', NULL, 1, '2024-12-19', '2024-12-18 23:09:27'),
(78, 18, 'date_start_cash', 'Fecha Inicial', 'datetime', NULL, 1, '2024-12-19', '2024-12-18 23:09:27'),
(79, 18, 'date_end_cash', 'Fecha Final', 'datetime', NULL, 1, '2024-12-19', '2024-12-18 23:09:27'),
(80, 18, 'id_admin_cash', 'Administrador', 'relations', 'admins', 1, '2024-12-19', '2024-12-18 23:09:43'),
(81, 18, 'id_office_cash', 'Sucursal', 'relations', 'offices', 1, '2024-12-19', '2024-12-18 23:09:39'),
(82, 20, 'concept_bill', 'Concepto', 'text', NULL, 1, '2024-12-19', '2024-12-18 23:14:38'),
(83, 20, 'cost_bill', 'Costo', 'money', NULL, 1, '2024-12-19', '2024-12-18 23:14:38'),
(84, 20, 'date_bill', 'Fecha', 'timestamp', NULL, 1, '2024-12-19', '2024-12-18 23:14:39'),
(85, 20, 'id_admin_bill', 'Administrador', 'relations', 'admins', 1, '2024-12-19', '2024-12-19 15:48:06'),
(86, 20, 'id_office_bill', 'Sucursal', 'relations', 'offices', 1, '2024-12-19', '2024-12-19 15:55:46'),
(87, 2, 'name_admin', 'Nombre', 'text', NULL, 1, '2024-12-19', '2024-12-19 20:12:24'),
(88, 2, 'id_office_admin', 'Sucursal', 'relations', 'offices', 1, '2024-12-19', '2024-12-19 20:20:36'),
(89, 10, 'code_product', 'Código de Barras', 'text', NULL, 1, '2025-01-24', '2025-01-24 13:13:35'),
(90, 4, 'dni_office', 'NIT', 'text', NULL, 1, '2025-01-24', '2025-01-24 15:57:02'),
(102, 40, 'supplier_name', 'Proveedor', 'text', NULL, 1, '2025-10-29', '2025-10-29 20:02:41'),
(103, 40, 'supplier_contact', 'Contácto', 'text', NULL, 1, '2025-10-29', '2025-10-29 20:02:41'),
(115, 41, 'id_supplier_purchase', 'Proveedor', 'relations', 'suppliers', 1, '2025-10-31', '2025-10-31 23:07:32'),
(116, 41, 'id_office_purchase', 'Sucursal', 'relations', 'offices', 1, '2025-10-31', '2025-10-31 23:07:39'),
(117, 41, 'id_product_purchase', 'Producto', 'relations', 'products', 1, '2025-10-31', '2025-10-31 23:07:49'),
(118, 41, 'cost_purchase', 'Costo producto', 'money', NULL, 1, '2025-10-31', '2025-10-31 23:07:11'),
(119, 41, 'utility_purchase', 'Utilidad %', 'select', '100%,50%,150%,200%', 1, '2025-10-31', '2025-11-08 04:52:08'),
(120, 41, 'price_purchase', 'Precio venta', 'money', NULL, 1, '2025-10-31', '2025-10-31 23:07:11'),
(121, 41, 'qty_purchase', 'Cantidad', 'int', NULL, 1, '2025-10-31', '2025-10-31 23:07:11'),
(122, 41, 'invest_purchase', 'Inversión', 'money', NULL, 1, '2025-10-31', '2025-10-31 23:07:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `files`
--

CREATE TABLE `files` (
  `id_file` int(11) NOT NULL,
  `id_folder_file` int(11) DEFAULT 0,
  `name_file` text DEFAULT NULL,
  `extension_file` text DEFAULT NULL,
  `type_file` text DEFAULT NULL,
  `size_file` double DEFAULT 0,
  `link_file` text DEFAULT NULL,
  `thumbnail_vimeo_file` text DEFAULT NULL,
  `id_mailchimp_file` text DEFAULT NULL,
  `date_created_file` date DEFAULT NULL,
  `date_updated_file` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `files`
--

INSERT INTO `files` (`id_file`, `id_folder_file`, `name_file`, `extension_file`, `type_file`, `size_file`, `link_file`, `thumbnail_vimeo_file`, `id_mailchimp_file`, `date_created_file`, `date_updated_file`) VALUES
(2, 1, 'Vinagre (11)', 'png', 'image/png', 405934, 'https://pos.desarrolloweb24siete.com//views/assets/files/690253664dc6922.png', NULL, NULL, '2025-10-29', '2025-10-29 17:48:22'),
(3, 1, 'Vinagre (10)', 'png', 'image/png', 416491, 'https://pos.desarrolloweb24siete.com//views/assets/files/690253669277c22.png', NULL, NULL, '2025-10-29', '2025-10-29 17:48:22'),
(5, 1, 'Vinagre (2)', 'png', 'image/png', 450078, 'https://pos.desarrolloweb24siete.com//views/assets/files/69025366c5b8622.png', NULL, NULL, '2025-10-29', '2025-10-29 17:48:22'),
(6, 1, 'ChatGPT Image 22 oct 2025, 12_24_24 p.m.', 'png', 'image/png', 1472808, 'https://pos.desarrolloweb24siete.com//views/assets/files/69025436db56e50.png', NULL, NULL, '2025-10-29', '2025-10-29 17:51:50'),
(7, 1, 'VitalHerbs', 'png', 'image/png', 564976, 'https://pos.desarrolloweb24siete.com/views/assets/files/69027c36d94d230.png', NULL, NULL, '2025-10-29', '2025-10-29 20:42:30'),
(8, 1, 'Vinagre (12)', 'png', 'image/png', 434964, 'https://pos.desarrolloweb24siete.com/views/assets/files/690280fb326e451.png', NULL, NULL, '2025-10-29', '2025-10-29 21:02:51'),
(9, 1, 'Vinagre (6)', 'png', 'image/png', 417891, 'https://pos.desarrolloweb24siete.com/views/assets/files/690280fb3530c51.png', NULL, NULL, '2025-10-29', '2025-10-29 21:02:51'),
(10, 1, 'Vinagre (5)', 'png', 'image/png', 416664, 'https://pos.desarrolloweb24siete.com/views/assets/files/690280fb5ae1851.png', NULL, NULL, '2025-10-29', '2025-10-29 21:02:51'),
(11, 1, 'Vinagre (9)', 'png', 'image/png', 434308, 'https://pos.desarrolloweb24siete.com/views/assets/files/690280fb8968251.png', NULL, NULL, '2025-10-29', '2025-10-29 21:02:51'),
(12, 1, 'Golden Milk', 'png', 'image/png', 554308, 'https://pos.desarrolloweb24siete.com/views/assets/files/69053796c997b30.png', NULL, NULL, '2025-10-31', '2025-11-07 21:23:37'),
(13, 1, 'Viangre de Mora', 'jpeg', 'image/jpeg', 53055, 'https://pos.desarrolloweb24siete.com/views/assets/files/690ecc1c8438a36.jpeg', NULL, NULL, '2025-11-08', '2025-11-08 04:55:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `folders`
--

CREATE TABLE `folders` (
  `id_folder` int(11) NOT NULL,
  `name_folder` text DEFAULT NULL,
  `size_folder` text DEFAULT NULL,
  `total_folder` double DEFAULT 0,
  `max_upload_folder` text DEFAULT NULL,
  `url_folder` text DEFAULT NULL,
  `keys_folder` text DEFAULT NULL,
  `date_created_folder` date DEFAULT NULL,
  `date_updated_folder` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `folders`
--

INSERT INTO `folders` (`id_folder`, `name_folder`, `size_folder`, `total_folder`, `max_upload_folder`, `url_folder`, `keys_folder`, `date_created_folder`, `date_updated_folder`) VALUES
(1, 'Server', '200000000000', 5621477, '500000000', 'https://pos.desarrolloweb24siete.com', NULL, '2024-12-16', '2025-11-08 04:50:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modules`
--

CREATE TABLE `modules` (
  `id_module` int(11) NOT NULL,
  `id_page_module` int(11) DEFAULT 0,
  `type_module` text DEFAULT NULL,
  `title_module` text DEFAULT NULL,
  `suffix_module` text DEFAULT NULL,
  `content_module` text DEFAULT NULL,
  `width_module` int(11) DEFAULT 100,
  `editable_module` int(11) DEFAULT 1,
  `date_created_module` date DEFAULT NULL,
  `date_updated_module` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `modules`
--

INSERT INTO `modules` (`id_module`, `id_page_module`, `type_module`, `title_module`, `suffix_module`, `content_module`, `width_module`, `editable_module`, `date_created_module`, `date_updated_module`) VALUES
(1, 2, 'breadcrumbs', 'Administradores', NULL, NULL, 100, 1, '2024-12-16', '2024-12-16 21:46:23'),
(2, 2, 'tables', 'admins', 'admin', '', 100, 0, '2024-12-16', '2024-12-19 20:12:22'),
(3, 4, 'breadcrumbs', 'sucursales', '', '', 100, 1, '2024-12-17', '2024-12-16 23:10:34'),
(4, 4, 'tables', 'offices', 'office', '', 100, 1, '2024-12-17', '2024-12-16 23:17:23'),
(5, 5, 'breadcrumbs', 'clientes', '', '', 100, 1, '2024-12-18', '2024-12-18 19:33:43'),
(6, 5, 'tables', 'clients', 'client', '', 100, 1, '2024-12-18', '2024-12-18 19:37:39'),
(7, 6, 'breadcrumbs', 'categorías', '', '', 100, 1, '2024-12-18', '2024-12-18 20:12:25'),
(8, 6, 'tables', 'categories', 'category', '', 100, 1, '2024-12-18', '2024-12-18 20:14:59'),
(9, 7, 'breadcrumbs', 'productos', '', '', 100, 1, '2024-12-18', '2024-12-18 20:33:10'),
(10, 7, 'tables', 'products', 'product', '', 100, 1, '2024-12-18', '2024-12-18 20:38:30'),
(11, 8, 'breadcrumbs', 'compras', '', '', 100, 1, '2024-12-18', '2024-12-18 21:37:39'),
(13, 9, 'breadcrumbs', 'Órdenes', '', '', 100, 1, '2024-12-18', '2024-12-18 22:35:32'),
(14, 9, 'tables', 'orders', 'order', '', 100, 0, '2024-12-18', '2024-12-18 22:45:34'),
(15, 10, 'breadcrumbs', 'ventas', '', '', 100, 1, '2024-12-18', '2024-12-18 22:50:59'),
(16, 10, 'tables', 'sales', 'sale', '', 100, 0, '2024-12-18', '2024-12-18 22:54:24'),
(17, 11, 'breadcrumbs', 'caja', '', '', 100, 1, '2024-12-19', '2024-12-18 23:02:12'),
(18, 11, 'tables', 'cashs', 'cash', '', 100, 1, '2024-12-19', '2024-12-18 23:09:25'),
(19, 12, 'breadcrumbs', 'gastos', '', '', 100, 1, '2024-12-19', '2024-12-18 23:12:39'),
(20, 12, 'tables', 'bills', 'bill', '', 100, 1, '2024-12-19', '2024-12-18 23:14:38'),
(21, 1, 'custom', 'orders', '', '', 100, 1, '2024-12-20', '2024-12-20 16:00:40'),
(22, 1, 'custom', 'products', '', '', 50, 1, '2024-12-20', '2024-12-20 16:02:03'),
(23, 1, 'custom', 'panel', '', '', 50, 1, '2024-12-20', '2024-12-20 16:02:18'),
(39, 15, 'breadcrumbs', 'proveedores', '', '', 100, 1, '2025-10-29', '2025-10-29 19:59:36'),
(40, 15, 'tables', 'suppliers', 'supplier', '', 100, 1, '2025-10-29', '2025-10-29 20:02:41'),
(41, 8, 'tables', 'purchases', 'purchase', '', 100, 1, '2025-10-31', '2025-10-31 23:07:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `offices`
--

CREATE TABLE `offices` (
  `id_office` int(11) NOT NULL,
  `title_office` text DEFAULT NULL,
  `address_office` text DEFAULT NULL,
  `phone_office` text DEFAULT NULL,
  `dni_office` text DEFAULT NULL,
  `date_created_office` date DEFAULT NULL,
  `date_updated_office` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `offices`
--

INSERT INTO `offices` (`id_office`, `title_office`, `address_office`, `phone_office`, `dni_office`, `date_created_office`, `date_updated_office`) VALUES
(3, 'Sucursal JE', 'Calle sucre Esquina Cobija', '60836039', '42135423524-3', '2024-12-17', '2025-10-31 02:30:57'),
(4, 'Sucursal Montero', 'Calle montero N° 24', '7900900', '42135423524-3', '2025-10-30', '2025-10-31 19:09:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orders`
--

CREATE TABLE `orders` (
  `id_order` int(11) NOT NULL,
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
  `date_updated_order` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `orders`
--

INSERT INTO `orders` (`id_order`, `transaction_order`, `id_admin_order`, `id_client_order`, `subtotal_order`, `discount_order`, `tax_order`, `total_order`, `method_order`, `transfer_order`, `status_order`, `date_order`, `id_office_order`, `date_created_order`, `date_updated_order`) VALUES
(37, '997359461178', 1, 16, 450, 0, 0, 450, 'efectivo', '', 'Completada', '2025-10-31 23:51:28', 3, '2025-10-31', '2025-10-31 23:51:28'),
(39, '764951191835', 1, 14, 180, 0, 0, 180, 'efectivo', '', 'Completada', '2025-11-01 00:16:00', 3, '2025-10-31', '2025-11-01 00:16:00'),
(51, '294565881772', 1, 16, 100, 0, 0, 100, 'efectivo', '', 'Completada', '2025-11-01 01:30:00', 3, '2025-10-31', '2025-11-01 01:30:00'),
(53, '135424887762', 1, 16, 480, 14, 0, 466, 'tarjeta', '', 'Completada', '2025-11-01 01:55:29', 3, '2025-10-31', '2025-11-01 01:55:29'),
(54, '451826576589', 14, 15, 260, 5, 0, 255, 'transferencia', '13154', 'Completada', '2025-11-01 01:56:36', 3, '2025-10-31', '2025-11-01 01:56:36'),
(55, '412382728395', 14, 14, 420, 10, 0, 410, 'efectivo', '', 'Completada', '2025-11-01 01:57:36', 3, '2025-10-31', '2025-11-01 01:57:36'),
(56, '384391765821', 14, 14, 160, 5, 0, 155, 'efectivo', '', 'Completada', '2025-11-01 02:01:29', 3, '2025-10-31', '2025-11-01 02:01:29'),
(57, '586924181416', 1, 16, 570, 14, 0, 556, 'efectivo', '', 'Completada', '2025-11-04 03:02:22', 3, '2025-11-03', '2025-11-04 03:02:22'),
(59, '241997685326', 1, 14, 160, 5, 0, 155, 'efectivo', '', 'Completada', '2025-11-04 03:26:42', 3, '2025-11-03', '2025-11-04 03:26:42'),
(60, '942985627493', 1, 15, 160, 5, 0, 155, 'efectivo', '', 'Completada', '2025-11-04 04:02:14', 3, '2025-11-04', '2025-11-04 04:02:14'),
(61, '945926878319', 1, 16, 160, 5, 0, 155, 'efectivo', '', 'Completada', '2025-11-04 04:10:08', 3, '2025-11-04', '2025-11-04 04:10:08'),
(62, '319892573781', 1, 15, 270, 0, 0, 270, 'efectivo', '', 'Completada', '2025-11-04 04:11:54', 3, '2025-11-04', '2025-11-04 04:11:54'),
(63, '249371821796', 1, 14, 1000, 19, 0, 981, 'tarjeta', '', 'Completada', '2025-11-04 04:19:40', 4, '2025-11-04', '2025-11-04 04:19:40'),
(64, '489615853148', 14, 14, 450, 0, 0, 450, 'transferencia', '51515', 'Completada', '2025-11-04 18:45:25', 3, '2025-11-04', '2025-11-04 18:45:25'),
(65, '545742139859', 14, 16, 540, 0, 0, 540, 'efectivo', '', 'Completada', '2025-11-04 18:47:45', 3, '2025-11-04', '2025-11-04 18:47:45'),
(66, '869571447259', 1, 14, 270, 8, 0, 262, 'efectivo', '', 'Completada', '2025-11-07 20:39:22', 3, '2025-11-07', '2025-11-07 20:39:22'),
(67, '442255752137', 14, 15, 340, 8, 0, 332, 'efectivo', '', 'Completada', '2025-11-07 21:27:31', 3, '2025-11-07', '2025-11-07 21:27:31'),
(68, '668178146271', 14, 14, 90, 0, 0, 90, 'efectivo', '', 'Completada', '2025-11-07 21:27:50', 3, '2025-11-07', '2025-11-07 21:27:50'),
(69, '564898521193', 14, 14, 90, 3, 0, 87, 'efectivo', '', 'Completada', '2025-11-07 21:39:03', 3, '2025-11-07', '2025-11-07 21:39:03'),
(70, '585964977638', 14, 14, 90, 3, 0, 87, 'efectivo', '', 'Completada', '2025-11-07 21:39:59', 3, '2025-11-07', '2025-11-07 21:39:59'),
(71, '267634721578', 14, 14, 90, 3, 0, 87, 'tarjeta', '', 'Completada', '2025-11-07 21:40:47', 3, '2025-11-07', '2025-11-07 21:40:47'),
(72, '139164815844', 14, 15, 90, 3, 0, 87, 'efectivo', '', 'Completada', '2025-11-07 21:45:02', 3, '2025-11-07', '2025-11-07 21:45:02'),
(73, '259183795615', 14, 16, 90, 3, 0, 87, 'efectivo', '', 'Completada', '2025-11-07 21:48:07', 3, '2025-11-07', '2025-11-07 21:48:07'),
(74, '695389841791', 14, 14, 90, 3, 0, 87, 'efectivo', '', 'Completada', '2025-11-07 21:54:13', 3, '2025-11-07', '2025-11-07 21:54:13'),
(75, '432179945872', 14, 15, 160, 5, 0, 155, 'tarjeta', '', 'Completada', '2025-11-07 21:58:28', 3, '2025-11-07', '2025-11-07 21:58:28'),
(76, '989346572592', 14, 14, 160, 5, 0, 155, 'tarjeta', '', 'Completada', '2025-11-07 21:59:34', 3, '2025-11-07', '2025-11-07 21:59:34'),
(77, '996586287497', 14, 15, 90, 0, 0, 90, 'efectivo', '', 'Completada', '2025-11-07 22:05:46', 3, '2025-11-07', '2025-11-07 22:05:46'),
(78, '683149716497', 14, 14, 90, 3, 0, 87, 'efectivo', '', 'Completada', '2025-11-07 22:12:08', 3, '2025-11-07', '2025-11-07 22:12:08'),
(79, '249922468557', 14, 15, 160, 5, 0, 155, 'tarjeta', '', 'Completada', '2025-11-07 22:16:55', 3, '2025-11-07', '2025-11-07 22:16:55'),
(80, '358186753427', 14, 14, 160, 5, 0, 155, 'tarjeta', '', 'Completada', '2025-11-07 22:17:12', 3, '2025-11-07', '2025-11-07 22:17:12'),
(81, '187833718612', 14, 15, 160, 5, 0, 155, 'tarjeta', '', 'Completada', '2025-11-07 22:18:52', 3, '2025-11-07', '2025-11-07 22:18:52'),
(82, '238594925138', 14, 16, 90, 0, 0, 90, 'efectivo', '', 'Completada', '2025-11-07 22:30:50', 3, '2025-11-07', '2025-11-07 22:30:50'),
(83, '453212231776', 14, 15, 90, 0, 0, 90, 'tarjeta', '', 'Completada', '2025-11-07 22:31:19', 3, '2025-11-07', '2025-11-07 22:31:19'),
(84, '554792314211', 14, 0, 0, 0, 0, 0, NULL, NULL, 'Pendiente', '2025-11-07 22:31:26', 3, '2025-11-07', '2025-11-07 22:31:26'),
(85, '143452613189', 14, 14, 160, 5, 0, 155, 'tarjeta', '', 'Completada', '2025-11-07 22:31:50', 3, '2025-11-07', '2025-11-07 22:31:50'),
(86, '826536947531', 14, 14, 250, 5, 0, 245, 'efectivo', '', 'Completada', '2025-11-08 04:39:55', 3, '2025-11-08', '2025-11-08 04:39:55'),
(87, '142479538853', 14, 15, 95, 0, 0, 95, 'efectivo', '', 'Completada', '2025-11-08 04:57:00', 3, '2025-11-08', '2025-11-08 04:57:00'),
(89, '177385634896', 14, 16, 90, 0, 0, 90, 'tarjeta', '', 'Completada', '2025-11-08 04:57:47', 3, '2025-11-08', '2025-11-08 04:57:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pages`
--

CREATE TABLE `pages` (
  `id_page` int(11) NOT NULL,
  `title_page` text DEFAULT NULL,
  `url_page` text DEFAULT NULL,
  `icon_page` text DEFAULT NULL,
  `type_page` text DEFAULT NULL,
  `order_page` int(11) DEFAULT 1,
  `date_created_page` date DEFAULT NULL,
  `date_updated_page` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `pages`
--

INSERT INTO `pages` (`id_page`, `title_page`, `url_page`, `icon_page`, `type_page`, `order_page`, `date_created_page`, `date_updated_page`) VALUES
(1, 'POS', 'pos', 'bi bi-house-door-fill', 'modules', 1, '2024-12-16', '2025-10-31 20:18:40'),
(2, 'Admins', 'admins', 'bi bi-person-fill-gear', 'modules', 4, '2024-12-16', '2025-10-31 20:18:40'),
(3, 'Archivos', 'archivos', 'bi bi-file-earmark-image', 'custom', 15, '2024-12-16', '2025-11-01 00:34:29'),
(4, 'Sucursales', 'sucursales', 'bi bi-shop', 'modules', 3, '2024-12-17', '2025-10-31 20:18:40'),
(5, 'Clientes', 'clientes', 'bi bi-people', 'modules', 5, '2024-12-18', '2025-10-31 20:18:46'),
(6, 'Categorías', 'categorias', 'bi bi-card-list', 'modules', 6, '2024-12-18', '2025-10-31 20:18:46'),
(7, 'Productos', 'productos', 'bi bi-box', 'modules', 8, '2024-12-18', '2025-10-31 20:18:46'),
(8, 'Compras', 'compras', 'bi bi-basket-fill', 'modules', 9, '2024-12-18', '2025-10-31 20:18:46'),
(9, 'Órdenes', 'ordenes', 'bi bi-ticket-detailed', 'modules', 10, '2024-12-18', '2025-10-31 20:18:46'),
(10, 'Ventas', 'ventas', 'bi bi-cash-coin', 'modules', 11, '2024-12-18', '2025-10-31 20:18:46'),
(11, 'Caja', 'caja', 'fas fa-cash-register', 'modules', 2, '2024-12-19', '2025-10-31 20:18:40'),
(12, 'Gastos', 'gastos', 'fas fa-money-bill-wave', 'modules', 12, '2024-12-19', '2025-10-31 20:18:46'),
(15, 'Proveedores', 'proveedores', 'bi bi-person-bounding-box', 'modules', 7, '2025-10-29', '2025-10-31 20:18:46'),
(16, 'Reportes', 'reports', 'bi bi-file-earmark-excel-fill', 'custom', 14, '2025-10-31', '2025-11-01 00:34:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `products`
--

CREATE TABLE `products` (
  `id_product` int(11) NOT NULL,
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
  `date_updated_product` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `products`
--

INSERT INTO `products` (`id_product`, `title_product`, `img_product`, `id_category_product`, `sku_product`, `unit_product`, `rte_product`, `stock_product`, `discount_product`, `status_product`, `id_office_product`, `code_product`, `date_created_product`, `date_updated_product`) VALUES
(12, 'Vinagre+de+Fresa', 'https%3A%2F%2Fpos.desarrolloweb24siete.com%2F%2Fviews%2Fassets%2Ffiles%2F69025366c5b8622.png', 8, 'VF1', '1Lt', NULL, '0', 0, 0, 3, '133331441', '2025-10-31', '2025-11-04 18:48:17'),
(13, 'Vinagre+de+Fresa', 'https%3A%2F%2Fpos.desarrolloweb24siete.com%2F%2Fviews%2Fassets%2Ffiles%2F69025366c5b8622.png', 8, 'VF1', '1Lt', NULL, '16', 0, 1, 4, '133331441', '2025-10-31', '2025-11-04 04:19:54'),
(14, 'Green+Powder', 'https%3A%2F%2Fpos.desarrolloweb24siete.com%2Fviews%2Fassets%2Ffiles%2F69027c36d94d230.png', 10, 'VHGP10', '500gr', NULL, '9', 3, 1, 3, '33322212215', '2025-10-31', '2025-11-08 04:40:07'),
(15, 'Green+Powder', 'https%3A%2F%2Fpos.desarrolloweb24siete.com%2Fviews%2Fassets%2Ffiles%2F69027c36d94d230.png', 10, 'VHGP10', '500gr', NULL, '21', 3, 1, 4, '33322212215', '2025-10-31', '2025-11-04 04:19:54'),
(16, 'Vinagre+de+Pitaya', 'https%3A%2F%2Fpos.desarrolloweb24siete.com%2Fviews%2Fassets%2Ffiles%2F690280fb8968251.png', 8, 'VP10', '1Lt', NULL, '11', 0, 1, 3, '14445455', '2025-11-04', '2025-11-08 04:40:07'),
(17, 'Vinagre+de+Pitaya', 'https%3A%2F%2Fpos.desarrolloweb24siete.com%2Fviews%2Fassets%2Ffiles%2F690280fb8968251.png', 8, 'VP10', '1Lt', NULL, '', 0, 1, 4, '14445455', '2025-11-04', '2025-11-04 17:36:55'),
(18, 'Vinagre de Durazno', 'https://pos.desarrolloweb24siete.com/views/assets/files/690280fb5ae1851.png', 8, 'VD10', '1Lt', NULL, '10', 3, 1, 3, '6061611165', '2025-11-04', '2025-11-07 21:48:27'),
(19, 'Vinagre+de+Durazno', 'https%3A%2F%2Fpos.desarrolloweb24siete.com%2Fviews%2Fassets%2Ffiles%2F690280fb5ae1851.png', 8, 'VD10', '1Lt', NULL, '', 0, 1, 4, '6061611165', '2025-11-04', '2025-11-04 17:38:51'),
(21, 'Vinagre de Perejil', 'https://pos.desarrolloweb24siete.com/views/assets/files/690280fb326e451.png', 8, 'dsf', '1Lt', NULL, '3', 3, 1, 3, '61511515', '2025-11-04', '2025-11-07 22:15:47'),
(22, 'Vinagre de Perejil', 'https://pos.desarrolloweb24siete.com/views/assets/files/690280fb326e451.png', 8, 'VP10', '1Lt', NULL, '', 0, 1, 4, '61511515', '2025-11-04', '2025-11-04 18:59:14'),
(23, 'Vinagre+de+Mel%C3%B3n', 'https%3A%2F%2Fpos.desarrolloweb24siete.com%2Fviews%2Fassets%2Ffiles%2F690280fb3530c51.png', 8, 'VM10', '1Lt', NULL, '2', 0, 1, 3, '151515511', '2025-11-04', '2025-11-08 04:57:54'),
(24, 'Vinagre+de+Mel%C3%B3n', 'https%3A%2F%2Fpos.desarrolloweb24siete.com%2Fviews%2Fassets%2Ffiles%2F690280fb3530c51.png', 8, 'VM10', '1Lt', NULL, '', 0, 1, 4, '151515511', '2025-11-04', '2025-11-04 19:01:32'),
(25, 'Vinagre+de+Mora', 'https%3A%2F%2Fpos.desarrolloweb24siete.com%2Fviews%2Fassets%2Ffiles%2F690ecc1c8438a36.jpeg', 8, 'VM10', '1Lt', NULL, '9', 0, 1, 3, '633361148481', '2025-11-08', '2025-11-08 04:58:06'),
(26, 'Vinagre+de+Mora', 'https%3A%2F%2Fpos.desarrolloweb24siete.com%2Fviews%2Fassets%2Ffiles%2F690ecc1c8438a36.jpeg', 8, 'VM10', '1Lt', NULL, '', 0, 1, 4, '633361148481', '2025-11-08', '2025-11-08 04:51:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `purchases`
--

CREATE TABLE `purchases` (
  `id_purchase` int(11) NOT NULL,
  `id_supplier_purchase` int(11) DEFAULT 0,
  `id_office_purchase` int(11) DEFAULT 0,
  `id_product_purchase` int(11) DEFAULT 0,
  `cost_purchase` double DEFAULT 0,
  `utility_purchase` text DEFAULT NULL,
  `price_purchase` double DEFAULT 0,
  `qty_purchase` int(11) DEFAULT 0,
  `invest_purchase` double DEFAULT 0,
  `date_created_purchase` date DEFAULT NULL,
  `date_updated_purchase` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `purchases`
--

INSERT INTO `purchases` (`id_purchase`, `id_supplier_purchase`, `id_office_purchase`, `id_product_purchase`, `cost_purchase`, `utility_purchase`, `price_purchase`, `qty_purchase`, `invest_purchase`, `date_created_purchase`, `date_updated_purchase`) VALUES
(3, 1, 3, 12, 45, '100%25', 90, 5, 225, '2025-10-30', '2025-11-01 00:15:08'),
(4, 1, 3, 12, 50, '100%25', 100, 15, 750, '2025-10-31', '2025-11-01 00:14:51'),
(5, 1, 4, 13, 45, '100%25', 90, 20, 900, '2025-10-31', '2025-11-01 01:53:23'),
(6, 1, 4, 15, 80, '100%25', 160, 25, 2000, '2025-10-31', '2025-11-01 01:53:52'),
(7, 1, 3, 14, 80, '100%25', 160, 30, 2400, '2025-10-31', '2025-11-01 01:54:43'),
(8, 1, 3, 18, 45, '100%25', 90, 15, 675, '2025-11-04', '2025-11-04 18:18:58'),
(9, 1, 3, 16, 45, '100%25', 90, 15, 675, '2025-11-04', '2025-11-04 18:19:23'),
(10, 1, 3, 21, 45, '100%25', 90, 15, 675, '2025-11-04', '2025-11-04 18:31:16'),
(11, 2, 3, 23, 45, '100%25', 90, 5, 225, '2025-11-04', '2025-11-04 19:05:32'),
(12, 1, 3, 25, 45, '100%25', 95, 10, 450, '2025-11-08', '2025-11-08 04:52:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sales`
--

CREATE TABLE `sales` (
  `id_sale` int(11) NOT NULL,
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
  `date_updated_sale` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `sales`
--

INSERT INTO `sales` (`id_sale`, `id_order_sale`, `id_product_sale`, `tax_type_sale`, `tax_sale`, `discount_sale`, `qty_sale`, `subtotal_sale`, `status_sale`, `id_admin_sale`, `id_client_sale`, `id_office_sale`, `date_created_sale`, `date_updated_sale`) VALUES
(41, 37, 12, '3%25', 0, 0, 5, 450, 'Completada', 1, 16, 3, '2025-10-31', '2025-10-31 23:51:28'),
(43, 39, 12, '3%25', 0, 0, 2, 180, 'Completada', 1, 14, 3, '2025-10-31', '2025-11-01 00:16:00'),
(64, 51, 12, '3%25', 0, 0, 1, 100, 'Completada', 1, 16, 3, '2025-10-31', '2025-11-01 01:30:00'),
(65, 53, 14, '3%25', 0, 3, 3, 480, 'Completada', 1, 16, 3, '2025-10-31', '2025-11-01 01:55:29'),
(66, 54, 12, '3%25', 0, 0, 1, 100, 'Completada', 14, 15, 3, '2025-10-31', '2025-11-01 01:56:36'),
(67, 54, 14, '3%25', 0, 3, 1, 160, 'Completada', 14, 15, 3, '2025-10-31', '2025-11-01 01:56:37'),
(68, 55, 14, '3%25', 0, 3, 2, 320, 'Completada', 14, 14, 3, '2025-10-31', '2025-11-01 01:57:36'),
(69, 55, 12, '3%25', 0, 0, 1, 100, 'Completada', 14, 14, 3, '2025-10-31', '2025-11-01 01:57:37'),
(70, 56, 14, '3%25', 0, 3, 1, 160, 'Completada', 14, 14, 3, '2025-10-31', '2025-11-01 02:01:29'),
(71, 57, 14, '3%25', 0, 3, 3, 480, 'Completada', 1, 16, 3, '2025-11-03', '2025-11-04 03:02:22'),
(72, 57, 12, '3%25', 0, 0, 1, 90, 'Completada', 1, 16, 3, '2025-11-03', '2025-11-04 03:02:22'),
(77, 59, 14, '', 0, 3, 1, 160, 'Completada', 1, 14, 3, '2025-11-03', '2025-11-04 03:26:42'),
(78, 60, 14, '', 0, 3, 1, 160, 'Completada', 1, 15, 3, '2025-11-03', '2025-11-04 04:00:39'),
(79, 61, 14, '', 0, 3, 1, 160, 'Completada', 1, 16, 3, '2025-11-04', '2025-11-04 04:10:08'),
(80, 62, 12, '', 0, 0, 3, 270, 'Completada', 1, 15, 3, '2025-11-04', '2025-11-04 04:11:54'),
(81, 63, 13, '', 0, 0, 4, 360, 'Completada', 1, 14, 4, '2025-11-04', '2025-11-04 04:19:40'),
(82, 63, 15, '', 0, 3, 4, 640, 'Completada', 1, 14, 4, '2025-11-04', '2025-11-04 04:19:40'),
(83, 64, 21, '', 0, 0, 5, 450, 'Completada', 14, 14, 3, '2025-11-04', '2025-11-04 18:45:25'),
(84, 65, 12, '', 0, 0, 6, 540, 'Completada', 14, 16, 3, '2025-11-04', '2025-11-04 18:47:45'),
(85, 66, 21, '', 0, 3, 2, 180, 'Completada', 1, 14, 3, '2025-11-07', '2025-11-07 20:39:22'),
(86, 66, 18, '', 0, 3, 1, 90, 'Completada', 1, 14, 3, '2025-11-07', '2025-11-07 20:39:22'),
(88, 67, 21, '', 0, 3, 1, 90, 'Completada', 14, 15, 3, '2025-11-07', '2025-11-07 21:27:03'),
(89, 67, 14, '', 0, 3, 1, 160, 'Completada', 14, 15, 3, '2025-11-07', '2025-11-07 21:27:03'),
(90, 67, 16, '', 0, 0, 1, 90, 'Completada', 14, 15, 3, '2025-11-07', '2025-11-07 21:27:03'),
(91, 68, 23, '', 0, 0, 1, 90, 'Completada', 14, 14, 3, '2025-11-07', '2025-11-07 21:27:50'),
(92, 69, 18, '', 0, 3, 1, 90, 'Completada', 14, 14, 3, '2025-11-07', '2025-11-07 21:39:03'),
(93, 70, 18, '', 0, 3, 1, 90, 'Completada', 14, 14, 3, '2025-11-07', '2025-11-07 21:39:59'),
(94, 71, 18, '', 0, 3, 1, 90, 'Completada', 14, 14, 3, '2025-11-07', '2025-11-07 21:40:47'),
(95, 71, 21, '', 0, 3, 1, 90, 'Completada', 14, 14, 3, '2025-11-07', '2025-11-07 21:41:11'),
(96, 72, 21, '', 0, 3, 1, 90, 'Completada', 14, 15, 3, '2025-11-07', '2025-11-07 21:45:02'),
(97, 73, 18, '', 0, 3, 1, 90, 'Completada', 14, 16, 3, '2025-11-07', '2025-11-07 21:48:07'),
(98, 74, 21, '', 0, 3, 1, 90, 'Completada', 14, 14, 3, '2025-11-07', '2025-11-07 21:54:13'),
(99, 75, 14, '', 0, 3, 1, 160, 'Completada', 14, 15, 3, '2025-11-07', '2025-11-07 21:58:28'),
(100, 76, 14, '', 0, 3, 1, 160, 'Completada', 14, 14, 3, '2025-11-07', '2025-11-07 21:59:34'),
(101, 77, 23, '', 0, 0, 1, 90, 'Completada', 14, 15, 3, '2025-11-07', '2025-11-07 22:05:46'),
(102, 78, 21, '', 0, 3, 1, 90, 'Completada', 14, 14, 3, '2025-11-07', '2025-11-07 22:12:08'),
(104, 79, 14, '', 0, 3, 1, 160, 'Completada', 14, 15, 3, '2025-11-07', '2025-11-07 22:16:56'),
(105, 80, 14, '', 0, 3, 1, 160, 'Completada', 14, 14, 3, '2025-11-07', '2025-11-07 22:17:12'),
(106, 81, 14, '', 0, 3, 1, 160, 'Completada', 14, 15, 3, '2025-11-07', '2025-11-07 22:18:52'),
(107, 82, 16, '', 0, 0, 1, 90, 'Completada', 14, 16, 3, '2025-11-07', '2025-11-07 22:30:51'),
(108, 83, 16, '', 0, 0, 1, 90, 'Completada', 14, 15, 3, '2025-11-07', '2025-11-07 22:31:19'),
(109, 85, 14, '', 0, 3, 1, 160, 'Completada', 14, 14, 3, '2025-11-07', '2025-11-07 22:31:50'),
(111, 86, 14, '', 0, 3, 1, 160, 'Completada', 14, 14, 3, '2025-11-08', '2025-11-08 04:39:55'),
(112, 86, 16, '', 0, 0, 1, 90, 'Completada', 14, 14, 3, '2025-11-08', '2025-11-08 04:39:55'),
(113, 87, 25, '', 0, 0, 1, 95, 'Completada', 14, 15, 3, '2025-11-08', '2025-11-08 04:57:00'),
(115, 89, 23, '', 0, 0, 1, 90, 'Completada', 14, 16, 3, '2025-11-08', '2025-11-08 04:57:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suppliers`
--

CREATE TABLE `suppliers` (
  `id_supplier` int(11) NOT NULL,
  `supplier_name` text DEFAULT NULL,
  `supplier_contact` text DEFAULT NULL,
  `date_created_supplier` date DEFAULT NULL,
  `date_updated_supplier` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `suppliers`
--

INSERT INTO `suppliers` (`id_supplier`, `supplier_name`, `supplier_contact`, `date_created_supplier`, `date_updated_supplier`) VALUES
(1, 'J.E Bolivia', '79005900', '2025-10-29', '2025-10-29 20:17:42'),
(2, 'Vital+Herbs', '77959898', '2025-10-29', '2025-10-29 20:38:53');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id_admin`);

--
-- Indices de la tabla `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id_bill`);

--
-- Indices de la tabla `cashs`
--
ALTER TABLE `cashs`
  ADD PRIMARY KEY (`id_cash`);

--
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id_category`);

--
-- Indices de la tabla `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id_client`);

--
-- Indices de la tabla `columns`
--
ALTER TABLE `columns`
  ADD PRIMARY KEY (`id_column`);

--
-- Indices de la tabla `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id_file`);

--
-- Indices de la tabla `folders`
--
ALTER TABLE `folders`
  ADD PRIMARY KEY (`id_folder`);

--
-- Indices de la tabla `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id_module`);

--
-- Indices de la tabla `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`id_office`);

--
-- Indices de la tabla `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id_order`);

--
-- Indices de la tabla `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id_page`);

--
-- Indices de la tabla `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id_product`);

--
-- Indices de la tabla `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id_purchase`);

--
-- Indices de la tabla `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id_sale`);

--
-- Indices de la tabla `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id_supplier`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `admins`
--
ALTER TABLE `admins`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `bills`
--
ALTER TABLE `bills`
  MODIFY `id_bill` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `cashs`
--
ALTER TABLE `cashs`
  MODIFY `id_cash` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `categories`
--
ALTER TABLE `categories`
  MODIFY `id_category` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `clients`
--
ALTER TABLE `clients`
  MODIFY `id_client` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `columns`
--
ALTER TABLE `columns`
  MODIFY `id_column` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT de la tabla `files`
--
ALTER TABLE `files`
  MODIFY `id_file` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `folders`
--
ALTER TABLE `folders`
  MODIFY `id_folder` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `modules`
--
ALTER TABLE `modules`
  MODIFY `id_module` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de la tabla `offices`
--
ALTER TABLE `offices`
  MODIFY `id_office` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `orders`
--
ALTER TABLE `orders`
  MODIFY `id_order` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT de la tabla `pages`
--
ALTER TABLE `pages`
  MODIFY `id_page` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `products`
--
ALTER TABLE `products`
  MODIFY `id_product` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id_purchase` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `sales`
--
ALTER TABLE `sales`
  MODIFY `id_sale` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT de la tabla `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id_supplier` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
