<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
	<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
	<meta http-equiv="Pragma" content="no-cache">
	<meta http-equiv="Expires" content="0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar Menü</title>
	<?php
	function asset($path) {
		if (file_exists($path)) {
			return $path . '?v=' . filemtime($path);
		}
		return $path; // falls Datei fehlt, kein Fehler
	}
	?>
	<link rel="stylesheet" href="<?= asset('format.css') ?>">
	<script src="<?= asset('js/app.js') ?>"></script>

	<!-- Font Awesome für Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>