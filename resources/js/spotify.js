jQuery(function($){ 
	$("#spotify-login-btn").click(function()
    {
        // DIABLED MEANS USER IS ALREADY LOGGED IN
        if($(this).attr('disabled')=='disabled') {
            return false;
        }
        else {
            ajaxurl     =   spotify_exchanger.ajaxurl;
            login_type  =   'spotify';
            spotify_id = $("#spotify_id").val();
            jQuery.ajax({
                            type: 'POST',
                            url: ajaxurl,
                            data: {
                                'action'      :   'ajax_spotify_login',
                                'login_type'  :   login_type,
                                'spotify_id'  :   spotify_id,
                                _wpnonce: spotify_exchanger._spotify_login_nonce

                            },
                            success: function (data) {
                                location.href = data;
                            },
                            error: function (errorThrown) {

                            }
                        });//end ajax
        }
    });
});