<!DOCTYPE html>
<html lang="en">
<head>

  <!-- Basic Page Needs
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <meta charset="utf-8">
  <title>SustainApp | Search Barcodes</title>
  <meta name="description" content="sustainability sustainable app for corporate social responsibility">
  <meta name="author" content="JM2">

  <!-- Mobile Specific Metas
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- FONT
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <link href="//fonts.googleapis.com/css?family=Raleway:400,300,600" rel="stylesheet" type="text/css">

  <!-- CSS
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <link rel="stylesheet" href="/css/normalize.css">
  <link rel="stylesheet" href="/css/skeleton.css">
  <link rel="stylesheet" href="/css/sustainapp.css">

  <!-- Favicon
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <link rel="icon" type="image/png" href="/images/favicon.png">

</head>
<body>

  <!-- Primary Page Layout
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <?php include $_SERVER['DOCUMENT_ROOT'].'/modules/topbanner.html.php'; ?>

  <div class="container">
	<div class="row">
	<?php include 'displayResults.php'; ?>
	</div>
	
	<div class="row">
		<div class="one-third column searchcol">
			<center>
			<!-- image search -->
			<!-- need to override max-width of elemnts to inheret from div (check out twelve column css -->
			<input type="file" accept="image/*" capture="camera" class="bcimg srslyfit">
			<br>
			<button type="submit" class="srslyfit">Scan Barcode</button>
			</center>
		</div>
		<div class="one-third column searchcol"><center>
			<!-- barcode text box -->
			<input type="number" class="srslyfit">
			<br>
			<button type="submit" class="srslyfit">Search Barcode</button>
			</center>
		</div>
		<div class="one-third column searchcol">
			<center>
			<!-- company text box -->
			<input type="search" class="srslyfit">
			<br>
			<button type="submit" class="srslyfit">Search Company</button>
			</center>
		</div>
	</div>
  </div>

<!-- End Document
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
</body>
</html>
