<?php
	include($_SERVER['DOCUMENT_ROOT']."/modules/head.html.php");
?>
<body>
<?php
	include($_SERVER['DOCUMENT_ROOT']."/modules/header_nav.html.php");
?>
<div class="container">
	<div class="row">
		<a name="scan_start" id="scan_start"></a>
		<div id="searchresult" class="col-sm-8 column">
			<span id="user_alerter">Javascript must be enabled to use SustainApp</span>
			<h4 id="company_name"></h4>
			<span id="company_score"></span>
			<span id="product_name"></span>
			<span id="upc"></span>
			<span id="score_text"></span>
		</div>
		<div id="wrongresults" class="col-sm-4"></div>
	</div>

	<div class="row">
		<div class="col-sm-6 searchcol">
			<center>
			<!-- image search -->
			<button type="submit" onclick="$('#Take-Picture').click();" class="btnStd-full">Scan Barcode</button>
			<canvas width="320" height="240" id="bc_img_canvas" ></canvas>
			<input id="Take-Picture" type="file" accept="image/*" capture="camera">
			</center>
		</div>
		<div class="col-sm-6 searchcol">
			<center>
				<!-- barcode text box -->
				<input type="search" id="bc_input" class="btnStd">
				<br>
				<button type="submit" onclick="search.company();" class="btnStd-full">Search Company</button>
				<br>
				<button type="submit" onclick="search.barcode();" class="btnStd-full">Search Barcode</button>
			</center>
		</div>
	</div>
</div><!-- /container -->

<?php
	include($_SERVER['DOCUMENT_ROOT']."/modules/end_js.html.php");
?>

<!-- product lookup scripts -->
<script src="/assets/js/quagga/quagga.min.js"></script>
<script src="/assets/js/sustainapp_search.js"></script>
<script src="/assets/js/image_decoder.js"></script>


</body>
</html>
