$(function() {
  var App = {
    init: function() {
      $("#Take-Picture").on("change", function(e) {
        if (e.target.files && e.target.files.length) {
          App.decode(URL.createObjectURL(e.target.files[0]));
        }
      });
    },
    decode: function(src) {
      search.clearResultDisplay();
      $("#user_alerter").html("Processing image...<br>Bacrode processing may take up to a minute depending on the age of your device.");
      var config = {
        inputStream: {
          size: 800
        },
        locator: {
          patchSize: "medium",
          halfSample: false
        },
        numOfWorkers: 4,
        decoder: {
          readers: [  "upc_reader",
                      "upc_e_reader",
                      "ean_reader",
                      "ean_8_reader",
                      "code_128_reader",
                      "code_39_reader",
                      "codabar_reader"
                    ]
        },
        locate: true,
        src: src
      };
      Quagga.decodeSingle(config, function(result) {
        if( result && result.codeResult && result.codeResult.code ) {
          // was having issues with getting the error from result,
          // so just checking if the code exists...
          var code = result.codeResult.code;
          // var canvas = Quagga.canvas.dom.image;
          // console.log("canvas: ",canvas.toDataURL());
          // TODO: check the type of barcode
          // I think digit-eyes only works for UPCs
          search.scan(result.codeResult.code);
        } else {
          search.clearResultDisplay();
          $("#user_alerter").html("Unable not find a barcode in the image. Please try again.");
          // TODO: log failed decode
        }
      });
    },
  };

  App.init();
});
