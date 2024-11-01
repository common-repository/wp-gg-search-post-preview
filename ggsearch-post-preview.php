<?php
/*
Plugin Name: WP GG Search Post Preview
Plugin URI: http://wordpress.org/plugins/wp-gg-search-post-preview/
Description: This Plugin extends your GG Search Engine. It allows a Post/Page preview.
Author: Matthias Günter
Version: 1.0
Author URI: http://matthias-web.de
Licence: GPLv2
*/

add_action("gg_search_box_end", "gg_search_post_preview_box_end");
function gg_search_post_preview_box_end() {
    ?>
    <div id="gg-post-preview">
        <div class="gg-post-preview-title">
            <span></span>
            <i class="fa fa-circle-o-notch fa-spin" style="display:block"></i>
        </div>
        <div id="gg-post-preview-publishing-actions">
            <div>
                <i class="fa fa-key"></i>
                <span></span>
            </div>
            
            <div>
                <i class="fa fa-user"></i>
                <span></span>
            </div>
            
            <div>
                <i class="fa fa-calendar"></i>
                <span></span>
            </div>
        </div>
        <div id="gg-post-preview-content"></div>
    </div>
    <script type="text/javascript">
        "use strict";
        jQuery(document).ready(function($) {
            var gg = GG_HOOK,
                postPreviewTimeout,
                postID = 0, postTitle, postItem, postPreviewContainer = $("#gg-post-preview");
            
            function gg_post_preview_change(status, author, date, content) {
                var rows = $("#gg-post-preview-publishing-actions > div");
                rows.eq(0).find("span").html(status);
                rows.eq(1).find("span").html(author);
                rows.eq(2).find("span").html(date);
                $("#gg-post-preview-content").html(content);
            }
            
            gg.register("changed", function(objs, args) {
                clearTimeout(postPreviewTimeout);
                var name = args[1];
                if (name == "post" || name == "page") {
                    postItem = args[0];
                    postID = args[0].attr("data-id");
                    postTitle = args[0].children("span").html();
                    postPreviewTimeout = setTimeout(function() {
                        var pxTop = postItem.position().top, posTop, posBottom;
                        //alert(pxTop + ">" + (objs.container.height() - postPreviewContainer.height()));
                        if (pxTop > objs.container.height() - postPreviewContainer.height()) {
                            posTop = "";
                            posBottom = "5px";
                        }else{
                            posTop = pxTop + "px";
                            posBottom = "";
                        }
                        postPreviewContainer
                                .css("top", posTop)
                                .css("bottom", posBottom)
                                .stop()
                                .fadeIn();
                        postPreviewContainer.find(".gg-post-preview-title").html(postTitle);
                            
                        jQuery.ajax({
                    	    type: "POST",
                    	    url: ajaxurl,
                    	    data: {
                        		'action': 'gg_post_preview_action',
                        		'id': postID
                        	},
                    	    //invokeData: { term: value.toUpperCase() },
                    	    success: function(response) {
                    	        response = $.parseJSON(response);
                    	        gg_post_preview_change(response.status, response.post_author, response.post_date, response.post_content);
                    	    }
                        });
                    }, 500);
                }else{
                    postPreviewContainer.stop().fadeOut();
                }
            });
            
            gg.register("hide", function(objs) {
               postPreviewContainer.hide(); 
            });
        });
    </script>
    
    <style type="text/css">
        #gg-post-preview {
            display: none;
            position: absolute;
            left: -420px;
            width: 400px;
            height: 300px;
            background: white;
            border: 5px solid rgb(0, 115, 170);
            border-radius: 5px;
            overflow: hidden;
            -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.08);
            box-shadow: 0 1px 1px rgba(0,0,0,.08);
            line-height: 1;
            font-weight: normal;
            font-style: normal;
        }
        
        #gg-post-preview .gg-post-preview-title {
            background: rgb(0, 115, 170);
            padding: 7px 15px 11px 15px;
            font-size: 14px;
            color: white;
        }
        #gg-post-preview .gg-post-preview-title i {
            float: right;
            margin: 0px;
            margin-top: 0px;
        }
        
        #gg-post-preview #gg-post-preview-publishing-actions {
            background: #E1E1E1;
            padding: 3px 15px 5px 15px;
            font-size: 10px;
            color: rgba(0, 0, 0, 0.7);
            line-height: 1.8 !important;
            font-weight: lighter;
        }
        #gg-post-preview #gg-post-preview-publishing-actions > div > i{
            margin-right: 10px;
            opacity: 0.5;
            background: orange;
            border-radius: 99px;
            background: white;
            padding: 2px 7px;
            width: 15px;
            text-align: center;
            border-left: 5px solid rgb(0, 115, 170);
        }
        #gg-post-preview #gg-post-preview-content {
            height: 183px;
            padding: 10px 15px;
            overflow-y: scroll;
        }
        
        #gg-post-preview #gg-post-preview-content p:first-of-type {
            margin-top: 0px;
            padding-top: 0px;
        }
    </style>
    <?php
}

// Handler für die Ausgabe wenn AJAX aufruft
add_action( 'wp_ajax_gg_post_preview_action', 'gg_search_post_preview_action_callback' );
function gg_search_post_preview_action_callback() {
	$id = $_POST["id"];

	$result = array();
	$status = get_post_field("post_status", $id);
	switch ( $status ) {
    	case 'private':
    		$result["status"] = __('Privately Published');
    		break;
    	case 'publish':
    		$result["status"] = __('Published');
    		break;
    	case 'future':
    		$result["status"] = __('Scheduled');
    		break;
    	case 'pending':
    		$result["status"] = __('Pending Review');
    		break;
    	case 'draft':
    	case 'auto-draft':
    		$result["status"] = __('Draft');
    		break;
    }
    $result["post_content"] = wpautop(get_post_field("post_content", $id));
    $time = strtotime(get_post_field("post_date", $id));
    $result["post_date"] = date_i18n(get_option( 'date_format' ) . " @ " . get_option("time_format"), $time);
    $result["post_author"] = get_userdata(get_post_field("post_author", $id))->display_name;
	
	echo json_encode($result);
    
	wp_die();
}
?>