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
      console.log("Okay, so I'm decoding now...")
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
      // config = $.extend({}, state, {src: src});
      Quagga.decodeSingle(config, function(result) {});
    },
  };

  App.init();

  // Quagga.onProcessed(function(result) {
  //   console.log("Image processed");
  //   // check out demo for drawing boxes...
  // });

  Quagga.onDetected(function(result) {
    var code = result.codeResult.code,
    canvas = Quagga.canvas.dom.image;
    console.log("canvas: ",canvas.toDataURL());
    console.log("I found it! I found it!\n", result.codeResult.code);
    // TODO: check the type of barcode
    //        I think digit-eyes only works for UPCs
    search.scan(result.codeResult.code);
  });
});
