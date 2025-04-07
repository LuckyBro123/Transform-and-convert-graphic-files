<?php

// Настройки
$graphic_files = 'graphic_files'; // Путь к исходной папке
$xResolution = 400; // Желаемая ширина
$yResolution = 300; // Желаемая высота
$output_folder = $graphic_files . '__converted'; // Папка для сохранения результатов

// Создаём выходную папку, если её нет
if (!is_dir($output_folder)) {
	mkdir($output_folder, 0777, true);
}

// Функция для обработки одного файла
function processImage($file, $output_folder, $xResolution, $yResolution, $base_dir) {
	// Проверяем, является ли файл изображением
	$allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
	$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
	if (!in_array($extension, $allowed_extensions)) {
		return;
	}

	// Открываем изображение
	try {
		switch ($extension) {
			case 'jpg':
			case 'jpeg':
				$image = imagecreatefromjpeg($file);
				break;
			case 'png':
				$image = imagecreatefrompng($file);
				break;
			case 'webp':
				$image = imagecreatefromwebp($file);
				break;
			default:
				return;
		}
	} catch (Exception $e) {
		echo "Ошибка при открытии файла: $file\n";
		return;
	}

	// Получаем текущие размеры изображения
	$original_width = imagesx($image);
	$original_height = imagesy($image);

	// Вычисляем новые размеры с сохранением пропорций
	$ratio = min($xResolution / $original_width, $yResolution / $original_height);
	$new_width = intval($original_width * $ratio);

	// Создаём новое изображение с новыми размерами
	$new_image = imagecreatetruecolor($new_width, $new_height);
	imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);

	// Конструируем новый путь и имя файла с расширением .webp
	$relative_path = substr($file, strlen($base_dir)); // Относительный путь внутри исходной папки
	// Используем pathinfo для получения информации о пути
	$path_parts = pathinfo($relative_path);
	$relative_path_without_extension = $path_parts['dirname'] . DIRECTORY_SEPARATOR . $path_parts['filename'];
	$output_path = $output_folder . $relative_path_without_extension . '.webp';

	// Создаём директории, если они не существуют
	$output_dir = dirname($output_path);
	if (!is_dir($output_dir)) {
		mkdir($output_dir, 0777, true);
	}

	// Сохраняем файл в формате WebP
	imagewebp($new_image, $output_path);
	imagedestroy($image);
	imagedestroy($new_image);

	echo "Обработано: $file -> $output_path\n";
}

// Рекурсивная функция для обхода папок
function processDirectory($dir, $output_folder, $xResolution, $yResolution, $base_dir) {
	$files = scandir($dir);
	foreach ($files as $file) {
		if ($file === '.' || $file === '..') {
			continue;
		}

		$full_path = $dir . DIRECTORY_SEPARATOR . $file;
		if (is_dir($full_path)) {
			// Если это папка, рекурсивно обрабатываем её
			processDirectory($full_path, $output_folder, $xResolution, $yResolution, $base_dir);
		} else {
			// Если это файл, обрабатываем его
			processImage($full_path, $output_folder, $xResolution, $yResolution, $base_dir);
		}
	}
}

// Запускаем обработку
processDirectory($graphic_files, $output_folder, $xResolution, $yResolution, $graphic_files);

echo "Обработка завершена.\n";