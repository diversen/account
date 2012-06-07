<?php



?>
    <script type="text/javascript">
      $(document).ready(function() {
            $("#modalDiv").dialog({
                modal: true,
                autoOpen: false,
                center: true,
                dialogClass: "flora",
                height: '460',
                width: '420',
                draggable: false,
                resizeable:  true,   
                title: 'Log ind',
                close: function(event, ui) { 
                    
                    window.location = '/account/iframe/popup';
                    //location.href = '/account/facebook/index?return_url=' + 
                    //    encodeURIComponent(location.href)
                }
            });
            
            $('.flora.ui-dialog').css({position:"fixed"});
            $('#goToMyPage').click(
                function() {
                    url = '/account/iframe/index';
                    $("#modalDiv").dialog("open");
                    $("#modalIFrame").attr('src',url);
                    return false;
            });                 
      });
    </script>

        <?php print_r($_COOKIE) ?>
        <p>         
        <a id="goToMyPage" href="#">Login</a>                       
        <div id="modalDiv"><iframe id="modalIFrame" width="100%" height="100%" marginWidth="0" marginHeight="0" frameBorder="0" scrolling="auto" title="Dialog Title"></iframe></div>

<!-- JS version -->
