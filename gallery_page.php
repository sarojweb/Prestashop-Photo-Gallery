<?php
global $smarty;
include( '../../config/config.inc.php' );
include( '../../header.php' );


class GalleryController{

    public function exec()  {

        if(isset($_GET['act']))  $act = $_GET['act'];
		if(isset($_POST['act'])) $act = $_POST['act'];

		if (isset($act)) $cb = 'on_'.strtolower( $act );
		if (!method_exists($this, $cb)) $cb = 'on_album_list';
		if (!method_exists($this, $cb)) return;

		$this->$cb();

    }

    private function on_album_list(){
        global $smarty;
        $sql = ' SELECT a.*, b.thumb FROM '._DB_PREFIX_.'gallery_albums AS a
            LEFT JOIN '._DB_PREFIX_.'gallery_photos AS b ON a.id = b.gallery_album_id
            GROUP BY a.id
			ORDER BY RAND(a.id) DESC';
        $datas = Db::getInstance()->ExecuteS($sql);
        $smarty->assign(array(
			'datas' => $datas
		));
        $smarty->display( dirname(__FILE__) . '/album.tpl' );
    }

    private function on_photo_list(){
        global $smarty;
        $album = Db::getInstance()->getRow('
            SELECT a.* FROM '._DB_PREFIX_.'gallery_albums AS a
            WHERE id ="' . $_GET['gallery_album_id'] . '"
        ');
        $sql = 'SELECT a.*, b.name AS album
		FROM '._DB_PREFIX_.'gallery_photos AS a
		LEFT JOIN '._DB_PREFIX_.'gallery_albums AS b on b.id = a.gallery_album_id
        WHERE a.gallery_album_id = "' . $_GET['gallery_album_id'] . '"
		ORDER BY a.id DESC';
        $datas = Db::getInstance()->ExecuteS($sql);
        $smarty->assign(array(
            'album' => $album,
			'datas' => $datas
		));
        $smarty->display( dirname(__FILE__) . '/photo.tpl' );
    }
}

$gallery = new GalleryController();
$gallery->exec();

include( '../../footer.php' );
?>