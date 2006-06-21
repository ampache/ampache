<script type="text/javascript" language="javascript">
<!-- Begin
// Set refresh interval (in seconds)
var refreshinterval=<?php echo conf('refresh_limit'); ?>;

function doLoad()
{
    // the timeout value should be the same as in the "refresh" meta-tag
    setTimeout( "refresh()", refreshinterval*1000 );
}

function refresh()
{
    //  This version of the refresh function will cause a new
    //  entry in the visitor's history.  It is provided for
    //  those browsers that only support JavaScript 1.0.
    //
    ajaxPut('<?php echo $ajax_url; ?>','<?php echo $ajax_object; ?>');
    doLoad();
}

// start with page-load
window.onload=doLoad();
// End -->
</script>

