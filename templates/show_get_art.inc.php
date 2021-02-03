<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */ ?>
<?php
      $limit     = AmpConfig::get('art_search_limit', Art::ART_SEARCH_LIMIT);
      $art_order = AmpConfig::get('art_order', array());
      $art_type  = ($object_type == 'album') ? T_('Cover Art Search') : T_('Artist Art Search');
      UI::show_box_top($art_type, 'box box_get_albumart'); ?>
<form enctype="multipart/form-data" name="coverart" method="post" action="<?php echo AmpConfig::get('web_path'); ?>/arts.php?action=find_art&object_type=<?php echo $object_type; ?>&object_id=<?php echo $object_id; ?>&burl=<?php echo base64_encode($burl); ?>&artist_name=<?php echo urlencode(Core::get_request('artist_name')); ?>&album_name=<?php echo urlencode(Core::get_request('album_name')); ?>&cover=<?php echo urlencode(Core::get_request('cover')); ?>" style="Display:inline;">
    <table class="gatherart">
        <?php
        foreach ($keywords as $key => $word) {
            if (($key != 'mb_albumid_group' && $key != 'mb_artistid') && ($key != 'keyword' && $word['label'])) { ?>
                <tr>
                    <td>
                        <?php echo $word['label']; ?>&nbsp;
                    </td>
                    <td>
                       <input type="text"
                    id="option_<?php echo $key . '"'; ?>
                    name="option_<?php echo $key; ?>"
                    value="<?php echo scrub_out(unhtmlentities($word['value'])); ?>"
                    <?php
                         if ($key == 'album') {
                             echo ' required';
                         } elseif ($key == 'artist') {
                             if ($object_type == 'artist') {
                                 echo 'required';
                             }
                         }
                     ?>
                    />
                  </td>
                </tr>
        <?php
            }
        } ?>
        <tr>
            <td>
                <?php echo T_('Direct URL to Image'); ?>
            </td>
            <td><input type="url" id="cover" name="cover" value="" /></td>
        </tr>
        <tr>
            <td>
                <?php echo T_('Local Image'); ?> (&lt; <?php echo UI::format_bytes(AmpConfig::get('max_upload_size')); ?>)
            </td>
            <td>
               <input type="file" id="file" name="file" value="" />
            </td>
        </tr>
       <?php
        if (in_array('spotify', $art_order)) {
            if ($object_type == 'album') {?>
      <tr>
             <th class="center" rowspan="3" style>
                <?php echo T_('Spotify Album Filters'); ?>
             </th>
             <td>
                <label for="for artistFilter"><?php echo T_('Artist'); ?></label>
                <input type="checkbox" id="artistFilter"
                    name="artist_filter" value="artist"
                     onchange="validateArtist()">
             </td>
         </tr>
        <tr>
           <td>
                <label id="gatherYear" for="yearFilter"><?php echo T_('Year'); ?> </label>
                <input type="text" id="yearFilter" name="year_filter" size="5" maxlength="9" pattern="[0-9]{4}(-[0-9]{4})?">
                <label><?php echo T_("(e.g. '2001', '2001-2005')"); ?></label>
           </td>
          </tr>
          <tr>
          <td>
             <label id="gatherLimit" for="searchLimit"> <?php echo T_('Limit'); ?></label>
              <input type="number" id="searchLimit"
                name="search_limit" required min="1" max="50" value="<?php echo $limit; ?>">
          </td>
          </tr>
            <?php } ?>
          <tr>
             <?php if ($object_type == 'artist') { ?>
             <td>
               <?php echo T_('Search Limit'); ?>
             </td>
             <td>
                  <input type="number" id="searchLimit"
                  name="search_limit" required min="1" max="50" value="<?php echo $limit; ?>">
              </td>

          <?php }
        } else { ?>
            <td>
            </td>
            <?php } ?>
            <td>
            </td>
          </tr>
        </table>
     <div class="formValidation">
        <input type="hidden" name="action" value="find_art" />
        <input type="hidden" name="object_type" value="<?php echo $object_type; ?>" />
        <input type="hidden" name="object_id" value="<?php echo $object_id; ?>" />
        <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo AmpConfig::get('max_upload_size'); ?>" />
        <?php if (AmpConfig::get('ajax_load')) {
            $cancelurl = ((string) AmpConfig::get('web_path') == '') ? $burl : (AmpConfig::get('web_path') . '/' . $burl);
        } else {
            $cancelurl = (string) $burl;
        }
         ?>
        <input type="button" value="<?php echo T_('Cancel'); ?>" onClick="NavigateTo('<?php echo $cancelurl; ?>');" />
        <input type="submit" value="<?php echo T_('Get Art'); ?>" />
    </div>
</form>
<?php UI::show_box_bottom(); ?>
<script>
    function validateArtist()
    {
       var artist = document.getElementById('option_artist');

       var checked = document.getElementById('artistFilter').checked;
       if (checked == true) {
         artist.setAttribute("required", "true");
       } else {
         artist.removeAttribute('required');
       }
    }
</script>