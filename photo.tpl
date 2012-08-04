<h2>{$album.name}</h2>
<script type="text/javascript" src="{$modules_dir}gallery/js/galleria-1.2.7.js"></script>
<div id="galleria" style='height:425px'>
    {foreach from=$datas item=data}
    <a href="{$modules_dir}gallery/img/{$data.name}">
        <img data-title=""
             data-description="{$data.description}"
             src="{$modules_dir}gallery/img/{$data.thumb}"
        >
    </a>
    {/foreach}
    <script>
    // Load the classic theme
    Galleria.loadTheme('{$modules_dir}gallery/js/classic/galleria.classic.min.js');
    // Initialize Galleria
    Galleria.run('#galleria');
    </script>
</div>