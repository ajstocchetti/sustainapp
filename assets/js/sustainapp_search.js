$(function() {
  var qDecoder = {
    init: function() {
      $("#Take-Picture").on("change", function(e) {
        if (e.target.files && e.target.files.length) {
          qDecoder.decode(URL.createObjectURL(e.target.files[0]));
        }
      });
    },
    config: {
      inputStream: {
        size: 800
      },
      locator: {
        patchSize: "medium",
        halfSample: false
      },
      numOfWorkers: 4,
      decoder: {
        readers: ["upc_reader",
          "upc_e_reader",
          "ean_reader",
          "ean_8_reader",
          "code_128_reader",
          "code_39_reader",
          "codabar_reader"
        ]
      },
      locate: true
    },
    decode: function(src) {
      clearResultDisplay();
      $("#user_alerter").html("Processing image...<br>Bacrode processing may take up to a minute depending on the age of your device.");
      $.extend(qDecoder.config, {
        src: src
      }); // add image src to quagga config
      Quagga.decodeSingle(qDecoder.config, function(result) {
        if (result && result.codeResult && result.codeResult.code) {
          // was having issues with getting the error from result,
          // so just checking if the code exists...
          var code = result.codeResult.code;
          // var canvas = Quagga.canvas.dom.image;
          // console.log("canvas: ",canvas.toDataURL());
          // TODO: check the type of barcode
          // I think digit-eyes only works for UPCs
          search.scan(result.codeResult.code);
        } else {
          clearResultDisplay();
          $("#user_alerter").html("Unable not find a barcode in the image. Please try again.");
          // TODO: log failed decode
        }
      });
    },
  }; // end qDecoder

  var search = {
    barcode: function() {
      this.fromText("UPC");
    },
    company: function() {
      this.fromText("COMPANY");
    },
    fromText: function(searchType) {
      var inpt = $('#bc_input').val();
      this.searchDB(inpt, searchType);
    },
    scan: function(scanCode) {
      // assumes scanCode is UPC
      this.searchDB(scanCode, "UPC");
    },

    // main search function. the above functions
    // all call into this one
    searchDB: function(inpt, searchType) {
      // make sure they entered something
      // but thorough validation is done on the backend
      // searchFromScan should always have a input, but being safe for now
      if (inpt == '') {
        return;
      }
      // clear out any text from previous search
      clearResultDisplay();
      $("#user_alerter").html("Searching for " + inpt + "...");
      // query for UPC/company
      var searchURL = "/api/search.php";
      var params = {
        "upcc": inpt,
        "searchtype": searchType
      };
      $.getJSON(searchURL, params, function(data, status) {
        if (!("PROGRESS" in data)) {
          $("#user_alerter").html("Something went wrong with the search. Please try again");
          return; // quit if no status returned
        }
        prog = data.PROGRESS;
        if (prog < 1000) { // don't show message if response code is over 999
          if (("MSG" in data) && (data["MSG"] != null))
            msg = data.MSG;
        }
        if (data.COMPANY) {
          comp = data.COMPANY;
          $("#company_name").html(comp);
        }
        if (data.RATING) {
          score = data.RATING;
          msg = getTextForScore(score);
          $("#company_score").html("Rating: <b>" + score + "</b><br>");
        }
        if (data.DESCRIPTION) {
          desc = data.DESCRIPTION;
          $("#product_name").html(desc + "<br>");
        }
        if (data.UPC) {
          upc = data.UPC;
          $("#upc").html("UPC: " + upc + "<hr class=\"result\">");
        }
        if (msg != '')
          $("#score_text").html(msg);

        $("#user_alerter").empty();
      });
    }
  }; // end search

  var clearResultDisplay = function() {
    $("#company_score").empty();
    $("#company_name").empty();
    $("#upc").empty();
    $("#product_name").empty();
    $("#score_text").empty();
    $("#user_alerter").empty();
  };

  var getTextForScore = function(score) {
    var texts = [];
    texts.A = "Companies with an A rating (A+/A/A-) are social and environmental leaders in their industry. It is our opinion that these companies were created specifically to provide socially and environmentally responsible options for consumers.";
    texts.B = "Companies with a B rating (B+/B/B-) are mainstream companies that are making significant progress in implementing behaviors that benefit people and the planet.";
    texts.C = "Companies with a C rating (C+/C/C-) have mixed social and environmental records, or there is insufficient data available to rank them.";
    texts.D = "Companies with a D rating (D+/D/D-) engage in practices that have significant negative impacts on people and the planet.";
    texts.F = "Companies with an F rating are actively engaging in the worst social and environmental practices in their industry.";

    score = score.charAt(0); // trim any + or -
    var retText = texts[score];
    retText += " CSR rating provided by <a href=\"http://www.betterworldshopper.com/rankings.html\" target=\"_blank\">Better World Shopper</a>.";
    return retText;
  };


  qDecoder.init();
  $("#user_alerter").html("Click 'Scan Barcode' to take a photo, or use the text box to manually enter a company name or the barcode of a product.");
});