--!
-- ammana.es - job protocols generator
-- https://github.com/NoLegalTech/ammana
-- Copyright (C) 2018 Zeres Abogados y Consultores Laborales SLP <zeres@zeres.es>
-- https://github.com/NoLegalTech/ammana/blob/master/LICENSE

-- phpMyAdmin SQL Dump
-- version 4.1.11
-- http://www.phpmyadmin.net
--
-- Host: hl172.dinaserver.com
-- Generation Time: Sep 12, 2017 at 07:27 PM
-- Server version: 5.5.57-0+deb8u1-log
-- PHP Version: 5.4.45-0+deb7u8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `ammana_pre`
--

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `email` varchar(64) COLLATE utf8_spanish_ci NOT NULL COMMENT 'email will identify each user',
  `password` varchar(32) COLLATE utf8_spanish_ci NOT NULL,
  `company_name` varchar(75) COLLATE utf8_spanish_ci NOT NULL,
  `cif` varchar(9) COLLATE utf8_spanish_ci NOT NULL,
  `address` varchar(116) COLLATE utf8_spanish_ci NOT NULL,
  `contact_person` varchar(100) COLLATE utf8_spanish_ci NOT NULL,
  `sector` int(11) NOT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
