function changeLanguage(lang) {
        var urlchangeLanguage = '/site/change-languge';
        $.ajax({ 
        type: "GET",
        url:urlchangeLanguage,
        data: {
        "language": lang,
        },                 
        success: function(res) {
            location.reload();
        },
        error: function(jqXHR, textStatus) {
            //alert(jqXHR);
            //alert(textStatus);
        }
        });
    }
