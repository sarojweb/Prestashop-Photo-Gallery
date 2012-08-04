
<div class="nodes gallery">
	<h2>Albums</h2>

    <div class="albums">
        <style>
            .albums ul li{
                margin:0px 0px 5px 10px;
                display:block;
                clear:both;
                list-style:none;
                padding:0px;
            }
        </style>
        {foreach from=$datas item=data}
            <div style="float: left; margin-top : 10px; margin-left: 10px; margin-right : 10px;">
                <h3 style='color:#A51859'>
                    {$data.name}
                </h3>
                <div style='background-color: #fff; border: 1px solid #C8C8C8; float:left; margin: -5px 5px 0px 0px; padding: 5px 5px 3px 5px; box-shadow: 0px 0px 5px #888;'>
                    <a href='{$base_dir}modules/gallery/gallery_page.php?act=photo_list&gallery_album_id={$data.id}'>
                        <img src='{$modules_dir}gallery/img/{$data.thumb}' alt="" />
                    </a>
                </div>
            </div>
        {/foreach}
    </div>

</div>