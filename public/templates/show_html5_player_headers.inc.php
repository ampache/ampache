<?php

use Ampache\Config\AmpConfig;
use Ampache\Module\Broadcast\Broadcast_Server;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\AssetCache;
use Ampache\Module\Util\Ui;

global $dic;
$ajaxUriRetriever = $dic->get(AjaxUriRetrieverInterface::class);
$web_path         = AmpConfig::get('web_path');
$cookie_string    = (make_bool(AmpConfig::get('cookie_secure')))
    ? "path: '/', secure: true, samesite: 'Strict'"
    : "path: '/', samesite: 'Strict'";
$iframed  = $iframed ?? false;
$is_share = $is_share ?? false;
if ($iframed || $is_share) { ?>
<link rel="stylesheet" href="<?php echo $web_path . Ui::find_template('jplayer.midnight.black-iframed.css', true) ?>" type="text/css" />
<?php
} else { ?>
<link rel="stylesheet" href="<?php echo $web_path . Ui::find_template('jplayer.midnight.black.css', true) ?>" type="text/css" />
<?php
    }

if (!$iframed) {
    require_once Ui::find_template('stylesheets.inc.php'); ?>
<link rel="stylesheet" href="<?php echo $web_path . Ui::find_template('jquery-editdialog.css', true); ?>" type="text/css" media="screen" />
<link rel="stylesheet" href="<?php echo $web_path; ?>/lib/modules/jquery-ui-ampache/jquery-ui.min.css" type="text/css" media="screen" />
<script src="<?php echo $web_path; ?>/lib/components/jquery/jquery.min.js"></script>
<script src="<?php echo $web_path; ?>/lib/components/jquery-ui/jquery-ui.min.js"></script>
<script src="<?php echo $web_path; ?>/lib/components/js-cookie/js-cookie-built.js"></script>
<script src="<?php echo AssetCache::get_url($web_path . '/lib/javascript/base.js'); ?>"></script>
<script src="<?php echo AssetCache::get_url($web_path . '/lib/javascript/ajax.js'); ?>"></script>
<script src="<?php echo AssetCache::get_url($web_path . '/lib/javascript/tools.js'); ?>"></script>
<script>
var jsAjaxServer = "<?php echo $ajaxUriRetriever->getAjaxServerUri(); ?>";
var jsAjaxUrl = "<?php echo $ajaxUriRetriever->getAjaxUri(); ?>";
</script>
<?php
} ?>
<link href="<?php echo $web_path; ?>/lib/modules/UberViz/style.css" rel="stylesheet" type="text/css">
<?php if (AmpConfig::get('webplayer_aurora')) { ?>
    <script src="<?php echo $web_path; ?>/lib/modules/aurora.js/aurora.js"></script>
<?php
} ?>
<script src="<?php echo $web_path; ?>/lib/components/happyworm-jplayer/dist/jplayer/jquery.jplayer.min.js"></script>
<script src="<?php echo $web_path; ?>/lib/components/happyworm-jplayer/dist/add-on/jplayer.playlist.min.js"></script>
<script src="<?php echo $web_path; ?>/lib/javascript/jplayer.ext.js"></script>
<script src="<?php echo $web_path; ?>/lib/javascript/jplayer.playlist.ext.js"></script>

<script>
var jplaylist = new Array();

function convertMediaToJPMedia(media)
{
    var jpmedia = {};
    jpmedia['title'] = media['title'];
    jpmedia['artist'] = media['artist'];
    jpmedia[media['filetype']] = media['url'];
    jpmedia['poster'] = media['poster'];
    jpmedia['artist_id'] = media['artist_id'];
    jpmedia['album_id'] = media['album_id'];
    jpmedia['media_id'] = media['media_id'];
    jpmedia['media_type'] = media['media_type'];
    jpmedia['replaygain_track_gain'] = media['replaygain_track_gain'];
    jpmedia['replaygain_track_peak'] = media['replaygain_track_peak'];
    jpmedia['replaygain_album_gain'] = media['replaygain_album_gain'];
    jpmedia['replaygain_album_peak'] = media['replaygain_album_peak'];
    jpmedia['r128_track_gain'] = media['r128_track_gain'];
    jpmedia['r128_album_gain'] = media['r128_album_gain'];

    return jpmedia;
}

function addMedia(media)
{
    const jpmedia = convertMediaToJPMedia(media);
    jplaylist.add(jpmedia);
}

function playNext(media)
{
    const jpmedia = convertMediaToJPMedia(media);
    jplaylist.addAfter(jpmedia, jplaylist.current);
}

function ExitPlayer()
{
    $("#webplayer").text('');
    $("#webplayer").hide();

<?php
if (AmpConfig::get('song_page_title')) {
        echo "window.parent.document.title = '" . addslashes(AmpConfig::get('site_title')) . "';";
    } ?>
    document.onbeforeunload = null;
}

function TogglePlayerVisibility()
{
    $("#webplayer").slideToggle()
}

function TogglePlaylistExpand()
{
    document.querySelector(".jp-playlist").classList.toggle('taller')
}

</script>
<?php
if ($iframed) { ?>
<script>
function NotifyOfNewSong(title, artist, icon)
{
    if (artist === null) {
        artist = '';
    }
    if (!("Notification" in window)) {
        console.error("This browser does not support desktop notification");
    } else {
        if (Notification.permission !== 'denied') {
            if (Notification.permission === 'granted') {
                NotifyBrowser(title, artist, icon);
            } else {
                Notification.requestPermission(function (permission) {
                    if (!('permission' in Notification)) {
                        Notification.permission = permission;
                    }
                    NotifyBrowser(title, artist, icon);
                });
            }
        } else {
            console.error("Desktop notification denied.");
        }
    }
}

function NotifyBrowser(title, artist, icon)
{
    var notyTimeout = <?php echo AmpConfig::get('browser_notify_timeout'); ?>;
    var notification = new Notification(title, {
        body: artist,
        icon: icon,
        silent: true
    });

    if (notyTimeout > 0) {
        setTimeout(function(){
            notification.close()
        }, notyTimeout * 1000);
    }
}

function NotifyOfNewArtist()
{
    refresh_slideshow();
}

function SwapSlideshow()
{
    swap_slideshow();
}

var visualizerHandler = {
    initialized: false,
    enabled: false,

    showVisualizer() {
        let selector = '#uberviz, #equalizerbtn, #header, #webplayer, #webplayer-minimize, .jp-interface, .jp-playlist'
        if (this.enabled) {
            this.enabled = false
            $('#equalizer').css('visibility', 'hidden')
            $(selector).removeClass('vizualizer')
        } else {
            // Resource not yet initialized? Do it.
            if (!this.initialized) {
                if ((typeof AudioContext !== 'undefined') || (typeof webkitAudioContext !== 'undefined')) {
                    UberVizMain.init();
                    this.initialized = true;
                    AudioHandler.loadMediaSource($('.jp-jplayer').find('audio').get(0));
                }
            }
            if (this.initialized) {
                this.enabled = true
                $(selector).addClass('vizualizer')
            } else {
                alert("<?php echo addslashes(T_("Your browser doesn't support this feature.")); ?>");
            }
        }
    },

    showVisualizerFullScreen() {
      if (!this.enabled) {
          this.showVisualizer()
      }

      var element = document.getElementById("viz");
      if (element.requestFullscreen) {
          element.requestFullscreen()
      } else {
          alert("<?php echo addslashes(T_('Full-Screen not supported by your browser')); ?>");
      }
    },

    showEqualizer(){
        if (this.enabled) {
            document.querySelector('#equalizer').classList.toggle("vizualizer")
        }
    }
}

var replaygainHandler = {
  audioContext: null,
  mediaSource: null,
  enabled: false,
  node: null,

  initAudioContext() {
    if (typeof AudioContext !== 'undefined') {
        this.audioContext = new AudioContext();
    } else if (typeof webkitAudioContext !== 'undefined') {
        this.audioContext = new webkitAudioContext();
    } else {
        this.audioContext = null;
    }
  },

  toggle() {
      if (this.node === null) {
          var mediaElement = $('.jp-jplayer').find('audio').get(0);
          if (mediaElement) {
              if (this.audioContext !== null) {
                  this.mediaSource = this.audioContext.createMediaElementSource(mediaElement);
                  this.node = this.audioContext.createGain();
                  this.node.gain.value = 1;
                  this.mediaSource.connect(this.node);
                  mediaSource = this.mediaSource
                  this.node.connect(this.audioContext.destination);
              }
          }
      }

      if (this.node != null) {
          this.enabled = !this.enabled;
          Cookies.set('replaygain', this.enabled, {<?php echo $cookie_string ?>});
          this.apply();
          let btn = document.querySelector('#replaygainbtn')
          if (this.enabled) {
              btn.classList.add('enabled')
          } else {
              btn.classList.remove('enabled')
          }
      }
  },

  apply() {
      if (this.node != null) {
          var gainlevel = 1;
          var replaygain = 0;
          var peakamplitude = 1;
          if (this.enabled && currentjpitem != null) {
              var replaygain_track_gain   = currentjpitem.attr("data-replaygain_track_gain");
              var r128_track_gain = currentjpitem.attr("data-r128_track_gain");

              if (r128_track_gain !== 'null') {
                  // R128 PREFERRED
                  replaygain = parseInt(r128_track_gain / 256); // LU/dB away from baseline of -23 LUFS/dB, stored as Q7.8 (2 ^ 8) https://tools.ietf.org/html/rfc7845.html#page-25
                  var referenceLevel = parseInt(-23); // LUFS https://en.wikipedia.org/wiki/EBU_R_128#Specification
                  var targetLevel = parseInt(-18); // LUFS/dB;
                  var masteredVolume = referenceLevel - replaygain;
                  var difference = targetLevel - masteredVolume;

                  gainlevel = (Math.pow(10, ((difference /* + Gpre-amp */) / 20)));
              } else if (replaygain_track_gain !== 'null') {
                  // REPLAYGAIN FALLBACK
                  replaygain = parseFloat(replaygain_track_gain);

                  if (replaygain != null) {
                      var track_peak = currentjpitem.attr("data-replaygain_track_peak");
                      if (track_peak !== 'null') {
                          peakamplitude = parseFloat(track_peak);
                      }
                      gainlevel = Math.min(Math.pow(10, ((replaygain /* + Gpre-amp */) / 20)), (1 / peakamplitude));
                  }
              }
          }

          this.node.gain.value = gainlevel;
      }
  }
}
replaygainHandler.initAudioContext();

function SavePlaylist()
{
    if (jplaylist['playlist'].length > 0) {
        var url = "<?php echo $ajaxUriRetriever->getAjaxUri(); ?>?page=playlist&action=append_item&item_type=" + jplaylist['playlist'][0]['media_type'] + "&item_id=";
        for (var i = 0; i < jplaylist['playlist'].length; i++) {
            url += "," + jplaylist['playlist'][i]["media_id"];
        }
        handlePlaylistAction(url, 'rb_append_dplaylist_new');
    }
}

function SaveToExistingPlaylist(event)
{
    if (jplaylist['playlist'].length > 0) {
        var item_ids = "";
        for (var i = 0; i < jplaylist['playlist'].length; i++) {
            item_ids += "," + jplaylist['playlist'][i]["media_id"];
        }
        showPlaylistDialog(event, jplaylist['playlist'][0]['media_type'], item_ids);
    }
}

</script>
<?php
} ?>
<script>
<?php if (AmpConfig::get('waveform') && !$is_share) { ?>
var shouts = {};

var waveformHandler = {
  clickTimer: null,
  click(songid, time) {
      // Double click
      if (this.clickTimer != null) {
          clearTimeout(this.clickTimer);
          this.clickTimer = null;
          NavigateTo('<?php echo $web_path ?>/shout.php?action=show_add_shout&type=song&id=' + songid + '&offset=' + time);
      } else {
          // Single click
          if (brconn === null) {
              this.clickTimer = setTimeout(function() {
                  this.clickTimer = null;
                  $("#jquery_jplayer_1").data("jPlayer").play(time);
              }, 250);
          }
      }
  },
  clickTimeOffset(e) {
      var parrentOffset = $(".waveform").offset().left;
      var offset = e.pageX - parrentOffset;
      var duration = $("#jquery_jplayer_1").data("jPlayer").status.duration;
      var time = duration * (offset / 400);

      return time;
  },
  show() {
      document.querySelector('.waveform').classList.remove("hidden")
  },
  hide() {
      document.querySelector('.waveform').classList.add("hidden")
  }
}

<?php
    } ?>

var brkey = '';
var brconn = null;

function startBroadcast(key)
{
    brkey = key;

    listenBroadcast();
    brconn.onopen = function(e) {
        sendBroadcastMessage('AUTH_SID', '<?php echo session_id(); ?>');
        sendBroadcastMessage('REGISTER_BROADCAST', brkey);
        sendBroadcastMessage('SONG', currentjpitem.attr("data-media_id"));
    };
}

function startBroadcastListening(broadcast_id)
{
    listenBroadcast();

    // Hide few UI information on listening mode
    $('.jp-previous').css('visibility', 'hidden');
    $('.jp-play').css('visibility', 'hidden');
    $('.jp-pause').css('visibility', 'hidden');
    $('.jp-next').css('visibility', 'hidden');
    $('.jp-stop').css('visibility', 'hidden');
    $('.jp-toggles').css('visibility', 'hidden');
    $('.jp-playlist').css('visibility', 'hidden');
    $('#broadcast').css('visibility', 'hidden');

    $('.jp-seek-bar').css('pointer-events', 'none');

    brconn.onopen = function(e) {
        sendBroadcastMessage('AUTH_SID', '<?php echo Stream::get_session(); ?>');
        sendBroadcastMessage('REGISTER_LISTENER', broadcast_id);
    };
}

function listenBroadcast()
{
    if (brconn != null) {
        stopBroadcast();
    }

    brconn = new WebSocket('<?php echo Broadcast_Server::get_address(); ?>');
    brconn.onmessage = receiveBroadcastMessage;
}

var brLoadingSong = false;
var brBufferingSongPos = -1;

function receiveBroadcastMessage(e)
{
    var jp = $("#jquery_jplayer_1").data("jPlayer");
    var msgs = e.data.split(';');

    for (var i = 0; i < msgs.length; ++i) {
        var msg = msgs[i].split(':');
        if (msg.length == 2) {
            switch (msg[0]) {
                case 'PLAYER_PLAY':
                    if (msg[1] == '1') {
                        if (jp.status.paused) {
                            jp.play();
                        }
                    } else {
                        if (!jp.status.paused) {
                            jp.pause();
                        }
                    }
                    break;
                case 'SONG':
                    addMedia($.parseJSON(atob(msg[1])));
                    brLoadingSong = true;
                    // Buffering song position in case it is asked in the next sec.
                    // Otherwise we will move forward on the previous song instead of the new currently loading one
                    setTimeout(function() {
                        if (brBufferingSongPos > -1) {
                            jp.play(brBufferingSongPos);
                            brBufferingSongPos = -1;
                        }
                        brLoadingSong = false;
                    }, 1000);
                    jplaylist.next();
                    break;
                case 'SONG_POSITION':
                    if (brLoadingSong) {
                        brBufferingSongPos = parseFloat(msg[1]);
                    } else {
                        jp.play(parseFloat(msg[1]));
                    }
                    break;
                case 'NB_LISTENERS':
                    $('#broadcast_listeners').html(msg[1]);
                    break;
                case 'INFO':
                    // Display information notification to user here
                    break;
                case 'ENDED':
                    jp.stop();
                    break;
                default:
                    alert('Unknown message code');
                    break;
            }
        }
    }
}

function sendBroadcastMessage(cmd, value)
{
    if (brconn != null && brconn.readyState == 1) {
        var msg = cmd + ':' + value + ';';
        brconn.send(msg);
    }
}

function stopBroadcast()
{
    brkey = '';
    if (brconn != null && brconn.readyState == 1) {
        brconn.close();
    }
    brconn = null;
}

<?php if ($iframed && AmpConfig::get('webplayer_confirmclose') && !$is_share) { ?>
window.parent.onbeforeunload = function (evt) {
    if (typeof $("#jquery_jplayer_1") !== 'undefined' && typeof $("#jquery_jplayer_1").data("jPlayer") !== 'undefined' && !$("#jquery_jplayer_1").data("jPlayer").status.paused &&
            (typeof document.activeElement === 'undefined' || (typeof document.activeElement.href !== 'undefined' && document.activeElement.href.indexOf('/batch.php') < 0 && document.activeElement.href.indexOf('/stream.php') < 0))) {
        var message = '<?php echo addslashes(T_('Media is currently playing, are you sure you want to close?')) . ' ' . AmpConfig::get('site_title') . '?'; ?>';
        if (typeof evt == "undefined") {
            evt = window.event;
        }
        if (evt) {
            evt.returnValue = message;
        }
        return message;
    }

    return null;
}
<?php
    } ?>
<?php if ($iframed && AmpConfig::get('webplayer_confirmclose') && !$is_share) { ?>
window.addEventListener('storage', function (event) {
  if (event.key == 'ampache-current-webplayer') {
    // The latest used webplayer is not this player, pause song if playing
    if (event.newValue != jpuqid) {
        if (!$("#jquery_jplayer_1").data("jPlayer").status.paused) {
            $("#jquery_jplayer_1").data("jPlayer").pause();
        }
    }
  }
});
<?php
    } ?>
</script>
