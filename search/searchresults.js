/*
$(document).ready(function() {
	$("#company_name").html("Annie's Homegrown fur real");
	$("#upc").html("0-13562-00004-3");
	$("#product_name").html("Shells and While Cheddar");
	$("#query_message").html("You did it! You got an A!");
	$("#company_score").html("A-");
	// now that everything is set, show the div
	$("#searchresult").css("display","block");
});
*/



function searchBarcode()
{	searchDB("UPC");	}
function searchCompany()
{	searchDB("COMPANY");	}

function searchDB(searchType)
{	var inpt = $('#bc_input').val();
	// validation is done on backend
	var searchURL = "../api/search.php";
	var params = {
		"upcc": inpt,
		"searchtype": searchType
	};
	$.getJSON(searchURL, params, function(data, status){
		var prog;
		var msg;
		var comp;
		var score;
		var upc;
		var desc;
		if( !("PROGRESS" in data))
			return;	// quit if no status returned
		prog = data.PROGRESS;
		if( "MSG" in data)
			msg = data.MSG;
		if( "RATING" in data)
			score = data.RATING;
		if( "COMPANY" in data)
			comp = data.COMPANY;
		if( "UPC" in data)
			upc = data.UPC;
		if( "DESCRIPTION" in data)
			desc = data.DESCRIPTION;
		// update page
		$("#company_name").html(comp);
		$("#upc").html(upc);
		$("#product_name").html(desc);
		$("#query_message").html(msg);
		$("#company_score").html(score);
		// show
		$("#searchresult").css("display","block");
	});
}

function testfunc()
{	alert("Test");
}
