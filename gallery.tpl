<style type="text/css">
.slideshow { height: 140px; width: 190px; margin: auto }
.slideshow a{ margin-left: 5px; margin-right: 5px; margin-top: 15px; }
.slideshow img { padding: 5px; border: 1px solid #ccc; background-color: #eee; margin: auto;}
</style>
<script type="text/javascript" src="{$modules_dir}gallery/js/jquery.cycle.all.latest.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    $('.slideshow').cycle({
		fx: 'fade' // choose your transition type, ex: fade, scrollUp, shuffle, etc...
	});
});
</script>
<!-- Block gallery -->
<div id="gallery_block_left" class="block">
  <h4>Photo Gallery</h4>
  <div class="block_content align_center slideshow">
		{foreach from=$datas item=data}
			<a href='{$base_dir}modules/gallery/gallery_page.php?act=photo_list&gallery_album_id={$data.gallery_album_id}'>
				<img src='{$imgPath}{$data.thumb}' alt="" />
			</a>
		{/foreach}
    </ul>
  </div>
</div>
<!-- /Block gallery -->