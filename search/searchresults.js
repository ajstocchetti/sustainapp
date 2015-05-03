function searchBarcode()
{	searchDB("UPC");	}
function searchCompany()
{	searchDB("COMPANY");	}

function searchDB(searchType)
{	var inpt = $('#bc_input').val();
	if( inpt == '')
	{	return;	}
	// make sure they entered something
	// but validation is done on the backend
	
	// clear out any text from previous search
	$("#company_score").empty();
	$("#company_name").empty();
	$("#upc").empty();
	$("#product_name").empty();

	// query for UPC/company
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
		if(prog < 1000) // don't show message if response code is over 999
		{	if( "MSG" in data)
				msg = data.MSG;
		}
		if( "COMPANY" in data)
		{	comp = data.COMPANY;
			$("#company_name").html(comp);
		}
		if( "RATING" in data)
		{	score = data.RATING;
			msg = getTextForScore(score);
			$("#company_score").html("Rating: <b>"+score+"</b><br>");
		}
		if( "DESCRIPTION" in data)
		{	desc = data.DESCRIPTION;
			$("#product_name").html(desc+"<br>");
		}
		if( "UPC" in data)
		{	upc = data.UPC;
			$("#upc").html("UPC: "+upc+"<br>");
		}
		if( msg != '')
			$("#query_message").html(msg);
		// show
		$("#searchresult").css("display","block");
	});
}

function getTextForScore(score)
{	score = score.charAt(0);	// trim any + or -
	switch (score)
	{	case "A":
			text = "Companies with an A rating (A+/A/A-) are social and environmental leaders in their industry. It is our opinion that these companies were created specifically to provide socially and environmentally responsible options for consumers.";
			break;
		case "B":
			text = "Companies with a B rating (B+/B/B-) are mainstream companies that are making significant progress in implementing behaviors that benefit people and the planet.";
			break;
		case "C":
			text = "Companies with a C rating (C+/C/C-) have mixed social and environmental records, or there is insufficient data available to rank them.";
			break;
		case "D":
			text = "Companies with a D rating (D+/D/D-) engage in practices that have significant negative impacts on people and the planet.";
			break;
		case "F":
			text = "Companies with an F rating are actively engaging in the worst social and environmental practices in their industry.";
			break;
	}
	return text;
}