-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 12, 2026 at 04:16 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cityguardian`
--
CREATE DATABASE IF NOT EXISTS `cityguardian` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `cityguardian`;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `issue_type` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `ai_description` text DEFAULT NULL,
  `ai_priority` enum('Critical','High','Medium','Low') DEFAULT NULL,
  `ai_department` varchar(100) DEFAULT NULL,
  `ai_confidence` decimal(5,2) DEFAULT NULL,
  `status` enum('Pending','Assigned','In Progress','Resolved') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `issue_type`, `location`, `description`, `image`, `ai_description`, `ai_priority`, `ai_department`, `ai_confidence`, `status`, `created_at`) VALUES
(3, 'potholes', '30, Jalan Rimbunan Melati, Laman Rimbunan, 52100 Kepong, Kuala Lumpur', 'Potholes', '1783067204_test_pothole.jpg', 'A large, deep pothole is visible in the asphalt road, partially filled with water, posing a hazard to road users.', 'High', 'DBKL Engineering Department', 98.00, 'Pending', '2026-07-03 08:26:49'),
(4, NULL, '30, Jalan Rimbunan Melati, Laman Rimbunan, 52100 Kepong, Kuala Lumpur', 'Bla Bla Bla', '1783067617_test_pothole.jpg', 'A large, deep pothole filled with water is visible on the asphalt road, indicating significant road damage and a potential hazard.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-03 08:33:42'),
(5, 'potholes', '30, Jalan Rimbunan Melati, Laman Rimbunan, 52100 Kepong, Kuala Lumpur', 'Bla Bla', '1783067827_test_pothole.jpg', 'A large and deep pothole is visible on the asphalt road, partially filled with water, indicating significant damage to the road surface.', 'High', 'DBKL Engineering Department', 98.00, 'Pending', '2026-07-03 08:37:11'),
(6, 'potholes', 'Jalan Rimbunan Melati, Laman Rimbunan, 52100 Kepong, Kuala Lumpur', 'Pothole in front of my house', '1783435586_1783067149_test_pothole.jpg', 'The image clearly shows a prominent, large pothole on an asphalt road surface. The pothole is somewhat oval-shaped and is partially filled with water, indicating a substantial depth. The surrounding areas of the road also appear to be degraded with loose gravel. No specific user description was provided, and the location of the issue is not mentioned.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-07 14:46:31'),
(7, 'potholes', 'Jalan Rimbunan Melati, Laman Rimbunan, 52100 Kepong, Kuala Lumpur', 'Pothole in front of my house', '1783435591_1783067149_test_pothole.jpg', 'The image clearly shows a significant pothole in the asphalt road surface. The pothole is large, deep, and contains standing water, which could obscure its true depth and make it more hazardous, especially for vehicles and motorcyclists. The surrounding road also appears worn with loose gravel, indicating general road deterioration. The user did not provide additional descriptive text, and the location information was not specified.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-07 14:46:36'),
(8, 'pothole', 'Jalan Rimbunan Melati, Laman Rimbunan, 52100 Kepong, Kuala Lumpur', 'Pothole in front of my house', '1783435755_1783067149_test_pothole.jpg', 'The image clearly shows a significant pothole in the asphalt road, which is filled with water, indicating its depth. The surrounding road surface also appears to be deteriorating with loose gravel. The user has reported this as a \'Pothole in front of my house\' located at Jalan Rimbunan Melati, Laman Rimbunan, 52100 Kepong, Kuala Lumpur. This poses a hazard to vehicles and pedestrians.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-07 14:49:21'),
(9, 'potholes', 'Jalan Rimbunan Melati, Laman Rimbunan, 52100 Kepong, Kuala Lumpur', 'Pothole in front of my house', '1783478490_1783067149_test_pothole.jpg', 'The image clearly shows a large, deep pothole in the asphalt road, partially filled with water, indicating its depth. The user reported this as a pothole located directly in front of their house at Jalan Rimbunan Melati, Laman Rimbunan, 52100 Kepong, Kuala Lumpur. The surrounding road surface also shows signs of wear and tear.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-08 02:41:35'),
(10, 'potholes', 'Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia', 'Inside Laman Rimbunan', '1783481228_1783067149_test_pothole.jpg', 'The image clearly depicts a large pothole on an asphalt road surface, partially filled with water. The surrounding road also shows signs of deterioration with loose gravel. This significant road defect poses a hazard to vehicles and is located inside Laman Rimbunan, specifically at Jalan Rimbunan Melati, Kepong, Kuala Lumpur.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-08 03:27:13'),
(11, 'potholes', 'Kuala Lumpur Middle Ring Road 2', '', '1783481418_1783481228_1783067149_test_pothole.jpg', 'The image clearly displays a significant pothole on what appears to be an asphalt road, partially filled with water. The surrounding road surface shows signs of wear and cracking, indicating general road deterioration around the cavity. This large pothole, located on the Kuala Lumpur Middle Ring Road 2, poses a considerable risk to road users.', 'High', 'DBKL Engineering Department', 98.00, 'Pending', '2026-07-08 03:30:22'),
(12, 'potholes', 'Kuala Lumpur Middle Ring Road 2', '', '1783482658_1783435591_1783067149_test_pothole.jpg', 'The image clearly depicts a large, deep pothole in the asphalt road surface. The pothole has accumulated water, suggesting its depth and potentially obscuring its full extent, making it a significant hazard. This issue is located on the Kuala Lumpur Middle Ring Road 2.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-08 03:51:04'),
(13, 'potholes', 'Jalan Kepong', '', '1783482771_1783067149_test_pothole.jpg', 'The image displays a large, deep pothole in the asphalt road surface, partially filled with water, indicating a significant road hazard. The surrounding road also shows signs of wear and loose gravel. No specific user description was provided. This issue is located at Jalan Kepong.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-08 03:52:56'),
(14, 'potholes', 'Jalan Kepng', '', '1783482889_1783067149_test_pothole.jpg', 'The image clearly depicts a large, deep pothole in the asphalt road surface at Jalan Kepng. The pothole is prominently filled with water, suggesting its significant depth and posing a substantial hazard to vehicles. The surrounding area of the road also shows signs of degradation with loose gravel.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-08 03:54:59'),
(15, 'potholes', 'Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia', '', '1783505893_1783067149_test_pothole.jpg', 'The image clearly depicts a large, deep pothole in an asphalt road, partially filled with water. The surrounding road surface also shows signs of cracking and wear, with loose gravel, indicating significant road degradation. Despite no user description provided, the visual evidence is very clear. This issue is located at Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia.', 'High', 'DBKL Engineering Department', 98.00, 'Pending', '2026-07-08 10:18:19'),
(16, 'potholes', 'Jalan Tun Razak', '', '1783506244_1783067149_test_pothole.jpg', 'The image clearly shows a large, deep pothole in the asphalt road surface, partially filled with water. The surrounding road also exhibits signs of wear and loose gravel. This significant road defect, located on Jalan Tun Razak, poses a hazard to vehicles and requires urgent attention.', 'High', 'DBKL Engineering Department', 98.00, 'Pending', '2026-07-08 10:24:09'),
(17, NULL, 'Sephora, Lorong Kuda, Kuala Lumpur City Centre (KLCC), Kampung Cendana, Kuala Lumpur, 50088, Malaysia', '', '', NULL, NULL, 'DBKL Engineering Department', NULL, 'Pending', '2026-07-08 10:41:36'),
(18, 'potholes', 'Jalan Tun Razak, Kampung Padang, Kampung Paya, Kampung Datuk Keramat, Kuala Lumpur, 50400, Malaysia', '', '1783507475_1783067149_test_pothole.jpg', 'The image clearly depicts a large and deep pothole on an asphalt road surface, with water accumulated at the bottom. The surrounding road shows signs of wear and loose debris. This significant road defect poses a hazard to vehicles and requires prompt attention. The reported location of this issue is Jalan Tun Razak, Kampung Padang, Kampung Paya, Kampung Datuk Keramat, Kuala Lumpur, 50400, Malaysia. No additional description was provided by the user.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-08 10:44:41'),
(19, 'pothole', 'Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia', '', '1783510933_1783067149_test_pothole.jpg', 'The image clearly depicts a large and deep pothole on an asphalt road surface, partially filled with water. The surrounding area shows loose gravel and signs of significant road degradation. Despite no specific user description, the visual evidence strongly indicates a hazardous road defect requiring urgent attention. This issue is located at Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-08 11:42:19'),
(20, 'potholes', 'Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia', '', '1783511356_1783067149_test_pothole.jpg', 'The image clearly depicts a significant pothole in the asphalt road surface. The pothole appears to be large and deep, filled with water, indicating substantial damage to the road structure. Surrounding the main hole, there are loose gravel and further cracks in the asphalt, suggesting the damage is spreading. No specific user description was provided, but the visual evidence is very clear. This issue is located on Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-08 11:49:20'),
(21, 'potholes', 'Jalan Gombak', '', '1783511786_1783067149_test_pothole.jpg', 'The image clearly displays a large, deep pothole in the asphalt road, located on Jalan Gombak. The pothole is filled with water, which can obscure its true depth and make it a significant hazard for drivers and motorcyclists, potentially causing vehicle damage or accidents. The surrounding road surface also shows signs of deterioration with loose gravel.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-08 11:56:31'),
(22, 'potholes', 'Jalan Tun Razak test', '', '1783511970_1783067149_test_pothole.jpg', 'The image clearly depicts a large, deep pothole on an asphalt road surface. The pothole is filled with water, suggesting it has accumulated rain or standing water, which can obscure its true depth and pose a significant hazard to vehicles. The surrounding road also shows signs of deterioration with loose gravel. This issue is located on Jalan Tun Razak test.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-08 11:59:35'),
(23, 'potholes', 'Jalan Tun Razak', '', '1783511992_1783067149_test_pothole.jpg', 'The image clearly depicts a large, deep pothole in the asphalt road surface at Jalan Tun Razak. The pothole is significant in size and appears to be partially filled with water, indicating its depth and potential for water accumulation. Loose gravel and broken asphalt are visible around the edges of the cavity, posing a hazard to road users.', 'High', 'DBKL Engineering Department', 98.00, 'Pending', '2026-07-08 11:59:58'),
(24, 'potholes', 'Jalan Tun Razak', '', '1783512128_1783067149_test_pothole.jpg', 'The image clearly depicts a significant pothole on the asphalt road at Jalan Tun Razak. The pothole is large and deep, and currently filled with water, indicating it has collected rainwater. The edges of the pothole show crumbling asphalt and loose gravel around the affected area, posing a hazard to vehicles and pedestrians.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-08 12:02:15'),
(25, 'pothole', 'Jalan Tun Razak', '', '1783518896_1783067149_test_pothole.jpg', 'The image clearly shows a significant pothole on the asphalt road, which is partially filled with water. The surrounding road surface also exhibits cracks and loose gravel, indicating deterioration. No specific user description was provided, but the issue is located at Jalan Tun Razak.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-08 13:55:02'),
(26, 'pothole', 'Jalan Tun Razak', '', '1783518902_1783067149_test_pothole.jpg', 'The image clearly depicts a large, deep pothole in the asphalt road surface on Jalan Tun Razak. The pothole is filled with water, indicating recent rain or poor drainage, and its edges show significant damage and loose gravel. This presents a considerable hazard to vehicles and motorcyclists, potentially causing damage or accidents.', 'High', 'DBKL Engineering Department', 98.00, 'Pending', '2026-07-08 13:55:07'),
(27, 'pothole', 'Jalan Kepong', '', '1783519320_1783067149_test_pothole.jpg', 'The image clearly shows a large and deep pothole in the asphalt road surface, filled with water. The surrounding area of the road also appears to be deteriorating with loose gravel. This significant road hazard, located at Jalan Kepong, poses a risk to vehicles and road users, especially given its size and water accumulation.', 'High', 'DBKL Engineering Department', 98.00, 'Pending', '2026-07-08 14:02:06'),
(28, 'pothole', 'Jalan Tun Razak', '', '1783519383_1783067149_test_pothole.jpg', 'The image clearly depicts a large and deep pothole on the asphalt road surface. Water has collected inside the pothole, indicating its depth and potential hazard. There is visible damage to the surrounding road material, with loose gravel present. This significant road defect is located on Jalan Tun Razak and poses a risk to vehicles.', 'High', 'DBKL Engineering Department', 98.00, 'Pending', '2026-07-08 14:03:09'),
(29, 'illegal_dumping', 'Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia', '', '1783520207_SamSlamsNK_NSTfield_image_socialmedia.var_1557309562.webp', 'The image clearly shows extensive illegal dumping with large piles of mixed waste, including construction debris, plastics, and various rubbish, accumulated on a large patch of land. No additional description was provided by the user. The issue is located at Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia.', 'Critical', 'DBKL Solid Waste Management & Public Cleansing Department', 100.00, 'Pending', '2026-07-08 14:16:51'),
(30, 'pothole', 'Jalan Tun Razak', '', '1783520360_1783067149_test_pothole.jpg', 'The image clearly depicts a significant pothole on an asphalt road surface, appearing large and deep, with standing water inside. There was no additional description provided by the user, but the issue is located at Jalan Tun Razak.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-08 14:19:25'),
(31, 'damaged_public_facility', 'Jalan Tun Razak', '', '1783520508_images.jpeg', 'The image clearly shows a severely damaged red plastic playground slide, located at Jalan Tun Razak. There is a large, irregular hole and a prominent crack near the bottom of the slide, indicating significant structural failure. This damage renders the public facility unsafe and unusable, posing a direct hazard to children. No additional user description was provided.', 'Critical', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-08 14:21:53'),
(32, 'damaged_public_facility', 'Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia', '', '1783521079_images.jpeg', 'The image displays a severely damaged children\'s slide, which is part of a public playground facility. There is a large, irregular hole and significant cracks on the main sliding surface, indicating a structural failure that poses a high risk of injury to users, particularly children. This issue is located at Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia.', 'Critical', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-08 14:31:23'),
(33, 'damaged_public_facility', 'Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia', '', '1783521168_images.jpeg', 'The image shows a severely damaged red playground slide at Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia. There is a large, irregular hole in the middle section of the slide, along with significant cracks extending downwards, making the equipment unsafe for use. This poses a potential hazard to children playing in the area.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-08 14:32:52'),
(34, 'damaged_public_facility', 'Jalan Rimbunan Melati', '', '1783521258_images.jpeg', 'The image displays a severely damaged red plastic playground slide located at Jalan Rimbunan Melati. A large, irregular hole and multiple cracks are visible on the sliding surface, particularly towards the lower section of the slide. This damage renders the facility unsafe for use, posing a high risk of injury to children.', 'Critical', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-08 14:34:24'),
(35, 'damaged_public_facility', 'Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia', '', '1783523670_images.jpeg', 'The image clearly shows a red plastic playground slide with a significant hole and crack on its surface, rendering it unsafe for use. This damage poses a direct safety hazard, especially for children who might attempt to use it. No additional user description was provided. The issue is located at Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-08 15:14:35'),
(36, 'damaged_public_facility', 'Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia', '', '1783523977_images.jpeg', 'The image clearly shows a significantly damaged red slide, which is part of a playground facility. There is a large, irregular hole and visible cracks on the sliding surface near the bottom, rendering it unsafe for use. This issue is located at Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia.', 'Critical', 'DBKL Landscape & Recreation Department', 100.00, 'Pending', '2026-07-08 15:19:44'),
(37, NULL, 'Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia', 'The slide in front of my house is broken', '1783524134_images.jpeg', NULL, NULL, 'DBKL Engineering Department', NULL, 'Pending', '2026-07-08 15:22:15'),
(38, 'pothole', 'Jalan Tun Razak', 'Pothole on the road', '1783605604_1783067149_test_pothole.jpg', 'The image clearly depicts a large pothole on an asphalt road surface, as confirmed by the user\'s description. The pothole is deep and wide enough to collect water, indicating significant damage to the road structure. This issue is located on Jalan Tun Razak.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-09 14:00:10'),
(39, 'pothole', 'Jalan Tun Abdul Razak', '', '1783605704_1783067149_test_pothole.jpg', 'The image displays a significant, deep pothole on an asphalt road surface. The pothole is partially filled with water, indicating its depth and potential hazard to vehicles. No specific user description was provided for this report. This issue is located on Jalan Tun Abdul Razak.', 'High', 'DBKL Engineering Department', 98.00, 'Pending', '2026-07-09 14:01:55'),
(40, NULL, 'Jalan Tun Abdul Razak', '', '1783605843_1783067149_test_pothole.jpg', NULL, NULL, 'DBKL Engineering Department', NULL, 'Pending', '2026-07-09 14:04:09'),
(41, 'pothole', 'Jalan Tun Abdul Razak', '', '1783606001_1783067149_test_pothole.jpg', 'The image clearly depicts a large and deep pothole on an asphalt road surface, partially filled with water. The edges of the pothole are irregular and show signs of crumbling, with loose gravel scattered around it. The user did not provide a description for this issue. The pothole is located on Jalan Tun Abdul Razak, which is identified as a federal road.', 'High', 'JKR', 98.00, 'Pending', '2026-07-09 14:06:48'),
(42, 'pothole', 'Jalan Tun Razak', '', '1783606064_1783067149_test_pothole.jpg', 'The image clearly shows a large, deep pothole on an asphalt road surface, with water collected inside. The pothole\'s size and depth appear significant, posing a hazard to vehicles. This issue is located on Jalan Tun Razak.', 'High', 'DBKL Engineering Department', 98.00, 'Pending', '2026-07-09 14:07:50'),
(43, 'pothole', 'Jalan Pelabuhan Klang', '', '1783606218_1783067149_test_pothole.jpg', 'The image clearly depicts a large and deep pothole on an asphalt road surface. The pothole is partially filled with water, suggesting its significant depth, and exhibits crumbling edges with loose debris around it. This represents a considerable hazard to road users, particularly vehicles, and is located on Jalan Pelabuhan Klang.', 'High', 'JKR', 98.00, 'Pending', '2026-07-09 14:10:25'),
(44, 'flooding', 'Jalan Tun Razak', 'River near my house has overflowed onto the street', '1783607813_PASCA_BANJIR_1683530775.webp', 'The image shows a large portable pump actively discharging murky water into a wide waterway that runs parallel to a road lined with buildings and palm oil trees. The user\'s description explicitly states that a river near their house has overflowed onto the street at Jalan Tun Razak. This indicates ongoing flooding from a major waterway, impacting the street and requiring active mitigation efforts as evidenced by the pumping operation.', 'High', 'JPS', 100.00, 'Pending', '2026-07-09 14:37:02'),
(45, 'flooding', 'Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia', 'The drain outside my house is clogged and water is pooling on the road', '1783608041_Blocked-Outside-Drain-With-Debris.webp', 'The image clearly shows a severely clogged drain with its metal cover partially dislodged, surrounded by a significant amount of muddy water pooling on a paved surface. The user explicitly states that the drain outside their house is clogged, causing water to pool on the road. This issue of localized flooding due to a non-functional drain is located at Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia.', 'High', 'DBKL Engineering Department', 100.00, 'Pending', '2026-07-09 14:40:50'),
(46, NULL, 'Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia', 'The drain outside my house is clogged and water is pooling on the road', '', NULL, NULL, 'DBKL Engineering Department', NULL, 'Pending', '2026-07-09 14:42:33'),
(47, 'flooding', 'Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia', '', '1783609828_PASCA_BANJIR_1683530775.webp', 'The image displays a large dewatering pump actively discharging a brownish, foamy stream of water into a narrow canal or waterway. The pump is situated on the bank of the canal, near some buildings, including one with a mosque-like dome, and an area surrounded by palm trees. Although no visible floodwaters are explicitly shown across the land, the presence and active operation of such heavy machinery strongly indicate an ongoing effort to manage or mitigate a flooding situation by draining excess water from a nearby area, likely due to overwhelmed local drainage. The incident is observed at Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur.', 'High', 'DBKL Engineering Department', 85.00, 'Pending', '2026-07-09 15:10:41'),
(48, 'flooding', 'Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, 52100, Malaysia', 'River near my house has overflowed onto the street', '1783609883_PASCA_BANJIR_1683530775.webp', 'The image depicts a large pump actively discharging water into a wide waterway or canal, which runs parallel to a road lined with palm trees, with buildings visible in the background. The user has reported that a river near their house has overflowed onto the street, indicating a significant flooding event originating from a major water body. This issue is located at Jalan Rimbunan Melati, Laman Rimbunan, Kepong, Kuala Lumpur, Malaysia.', 'High', 'JPS', 95.00, 'Pending', '2026-07-09 15:11:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `reset_token`, `reset_expires`) VALUES
(1, 'ahkaw@gmail.com', '123', NULL, NULL),
(2, 'alicelyh.2006@gmail.com', '$2y$10$wmZlGkP8mLEOblyKm93EP.DohZUuEaU7k8L3QAy80/AGkqWwQP8X2', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
