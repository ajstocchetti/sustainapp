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
  
  <!-- Scripts
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
  <script src="searchresults.js"></script>

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
		<div id="searchresult" class="two-thirds column">
			<div class="companyname"><h4 id="company_name"></h4></div>
			<span id="company_score"></span>
			<br>
			<span id="upc"></span>
			<br>
			<span id="product_name"></span>
			<p id="query_message"></p>
			<p id="ajs"></p>
			<p id="ajs1"></p>
			<p id="ajs2"></p>
		</div>
		<div id="wrongresults" class="one-third column"></div>
	</div>
	
	<div class="row">
		<div class="one-third column searchcol">
			<center>
			<!-- image search -->
			<input type="file" accept="image/*" capture="camera" class="bcimg srslyfit">
			<br>
			<button type="submit" class="srslyfit">Scan Barcode</button>
			</center>
		</div>
		<div class="one-third column searchcol"><center>
			<!-- barcode text box -->
			<input type="search" id="bc_input" class="srslyfit">
			<br>
			<button type="submit" onclick="searchBarcode();" class="srslyfit">Search Barcode</button>
			<br>
			<button type="submit" onclick="searchCompany();" class="srslyfit">Search Company</button>
			</center>
		</div>
	</div>
  </div>

<!-- End Document
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
</body>
</html>
