<?php

/**
 *
 * @author : Kurniawan <iam@iwanwan.me>
 * @since  : 2012-07-29
 *
 **/

if (!defined('_PS_VERSION_'))
	exit;

require_once( dirname(__FILE__) . '/imagetool.php' );
class Gallery extends Module
{
	public function __construct()
	{
		$this->name = 'gallery';
		$this->tab = 'front_office_features';
		$this->version = 0.1;
		$this->author = 'Kurniawan';
		$this->need_instance = 0;

		parent::__construct();

		$this->displayName = $this->l('Photo Gallery');
		$this->description = $this->l('Adds Photo Gallery on your store.');
		$this->confirmUninstall = $this->l('Are you sure you want to delete all your Gallery.?');
	}

	public function install()
	{
		//return (parent::install() AND $this->registerHook('top') && $this->registerHook('leftColumn') );
		if( !parent::install() OR
			!Db::getInstance()->Execute('
				CREATE TABLE '._DB_PREFIX_.'gallery_albums (
					`id` int NOT NULL AUTO_INCREMENT,
					`name` varchar(255) NOT NULL,
					`promoted` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
					`description` text NOT NULL,
					PRIMARY KEY(`id`)
				)
				ENGINE='._MYSQL_ENGINE_.' default CHARSET=utf8;
			') OR
			!Db::getInstance()->Execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'gallery_photos` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`gallery_album_id` int(11) NOT NULL,
					`name` varchar(255) NOT NULL,
					`thumb` varchar(255) NOT NULL,
					`description` text NOT NULL,
					PRIMARY KEY (`id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
			') OR
			!Db::getInstance()->Execute('
				INSERT INTO `'._DB_PREFIX_.'configuration`
				(`id_configuration`, `name`, `value`, `date_add`, `date_upd`)
				VALUES
				(NULL, \'PS_GALLERY_THUMB_HEIGHT\', \'80\', NOW(), NOW()),
				(NULL, \'PS_GALLERY_THUMB_WIDTH\', \'80\', NOW(), NOW()),
				(NULL, \'PS_GALLERY_HEIGHT\', \'324\', NOW(), NOW()),
				(NULL, \'PS_GALLERY_WIDTH\', \'712\', NOW(), NOW());
			')
		)
			return false;
		return true;
	}

	public function uninstall(){
		if (!parent::uninstall() OR
			!Db::getInstance()->Execute('DROP TABLE '._DB_PREFIX_.'gallery_albums') OR
			!Db::getInstance()->Execute('DROP TABLE '._DB_PREFIX_.'gallery_photos') OR
			!Db::getInstance()->Execute('DELETE FROM '._DB_PREFIX_.'configuration WHERE `name` LIKE \'PS_GALLERY_%\' ')
		){
			return true;
		}
		return true;
	}

	public function getContent()
	{
		global $currentIndex, $cookie;

		$this->_html = '<h2>'.$this->displayName.'</h2>';

		$this->_html .= '<a href="' . $currentIndex . '&configure=' .$this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&act=on_albums_lists">';
		$this->_html .= $this->l('List Albums');
		$this->_html .= '</a>';

		//add new album link
		$this->_html .= '&nbsp;<a href="' . $currentIndex . '&configure=' .$this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&act=albums_add">';
		$this->_html .= '<img src="'. _PS_ADMIN_IMG_ . 'add.gif" alt="" title="" /> '.$this->l('Add a new Albums');
		$this->_html .= '</a>';


		//add new photos link
		$this->_html .= '&nbsp;&nbsp;<a href="' . $currentIndex . '&configure=' .$this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&act=photos_add">';
		$this->_html .= '<img src="'. _PS_ADMIN_IMG_ . 'add.gif" alt="" title="" /> '.$this->l('Add a new Photos');
		$this->_html .= '</a>';

		//add new photos link
		$this->_html .= '&nbsp;&nbsp;<a href="' . $currentIndex . '&configure=' .$this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&act=configure">';
		$this->_html .= '<img src="'. _PS_ADMIN_IMG_ . 'add.gif" alt="" title="" /> '.$this->l('Configure');
		$this->_html .= '</a>';

		if(isset($_GET['act']))  $act = $_GET['act'];
		if(isset($_POST['act'])) $act = $_POST['act'];

		if (isset($act)) $cb = 'on_'.strtolower( $act );
		if (!method_exists($this, $cb)) $cb = 'on_albums_lists';
		if (!method_exists($this, $cb)) return;

		$this->$cb();
		return $this->_html;
	}

	/**
	 * {{{ Controller
	 **/
	private function on_albums_edit(){
		global $cookie;
		$val = $this->getAlbum($_GET['id']);
		if( Tools::isSubmit('submitEditAlbums') ){
			if ( empty($_POST['name']) )
				$this->_html .= $this->displayError($this->l('You must fill in mandatory fields'));
			else
				if ($this->editAlbums()){
					$this->_html .= $this->displayConfirmation($this->l('The Album has been updated.'));
					unset($_POST);
				}else{
					$this->_html .= $this->displayError($this->l('An error occurred during update, please try again.'));
				}

			$val = $_POST;
		}

		$this->_html .= '
		<fieldset>
			<form method="post" action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'">
				' . $this->fieldFormAlbum($val) . '
				<div class="margin-form">
					<input type="hidden" name="act" id="act" value="albums_edit" />
					<input type="hidden" name="id" id="id" value="' . $val['id'] . '" />
					<input type="submit" class="button" name="submitEditAlbums" value="'.$this->l('Edit Album').'" />
				</div>
			</form>
		</fieldset>';
	}
	private function on_albums_delete(){
		if (isset($_GET['id']))
		{
			if (!is_numeric($_GET['id']) OR !$this->deleteAlbum())
			 	$this->_html .= $this->displayError($this->l('An error occurred during deletion.'));
			else
			 	$this->_html .= $this->displayConfirmation($this->l('The Album has been deleted.'));
		}
		$this->on_albums_lists();
	}

	private function on_albums_add(){
		global $cookie;

		if( Tools::isSubmit('submitAddAlbums') ){
			if ( empty($_POST['name']) )
				$this->_html .= $this->displayError($this->l('You must fill in mandatory fields'));
			else
				if ($this->addAlbums()){
					$this->_html .= $this->displayConfirmation($this->l('The Album has been added.'));
					unset($_POST);
				}else{
					$this->_html .= $this->displayError($this->l('An error occurred during save, please try again.'));
				}
			$val = $_POST;
		}

		$this->_html .= '
		<fieldset>
			<form method="post" action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'">
				' . $this->fieldFormAlbum($val) . '
				<div class="margin-form">
					<input type="hidden" name="act" id="act" value="albums_add" />
					<input type="submit" class="button" name="submitAddAlbums" value="'.$this->l('Add Album').'" />
				</div>
			</form>
		</fieldset>';
	}
	private function on_albums_lists(){

		global $currentIndex, $cookie, $adminObj;
		$languages = Language::getLanguages();

		$albums = $this->getAlbums();
		$cl = $currentIndex . '&configure=' .$this->name . '&token=' . Tools::getAdminTokenLite('AdminModules');

		$this->_html .= '
		<h3 class="blue space">'.$this->l('Gallery Albums list').'</h3>
		<table class="table">
			<tr>
				<th>'.$this->l('ID').'</th>
				<th>'.$this->l('Album Name').'</th>
				<th>'.$this->l('Promoted').'</th>
				<th>'.$this->l('Description').'</th>
				<th>'.$this->l('Actions').'</th>
			</tr>';

		if (!$albums)
			$this->_html .= '
			<tr>
				<td colspan="3">'.$this->l('There are no Albums.').'</td>
			</tr>';
		else
			foreach ($albums AS $album){
				$promoted = ($album['promoted'] == 1 ) ? 'enabled.gif' : 'disabled.gif';
				$this->_html .= '
				<tr valign="top">
					<td>'.$album['id'].'</td>
					<td>'.$album['name'].'</td>
					<td align="center"><img src="../img/admin/' . $promoted . '" alt="" title="" /></td>
					<td>'.nl2br($album['description']).'</td>
					<td>
						<a href=' . $cl  . "&act=albums_edit&id=" . $album['id'] . '>
						<img src="../img/admin/edit.gif" alt="" title="" />
						</a>
						<a href=' . $cl  . "&act=albums_delete&id=" . $album['id'] . '>
						<img src="../img/admin/delete.gif" alt="" title="" />
						</a>
						<a href=' . $cl  . "&act=photos_lists&album_id=" . $album['id'] . '>
						<img src="'._PS_BASE_URL_.__PS_BASE_URI__.'modules/'.$this->name.'/logo.gif" alt="view_photos" />
						</a>
					</td>
				</tr>';
			}
		$this->_html .= '</table>';
	}
	private function on_photos_edit(){
		global $cookie;
		$val = $this->getPhoto($_GET['id']);
		if( Tools::isSubmit('submitEditPhotos') ){
			if ( empty($_POST['gallery_album_id']) )
				$this->_html .= $this->displayError($this->l('You must fill in mandatory fields'));
			else
				if ($this->editPhoto()){
					$this->_html .= $this->displayConfirmation($this->l('The Photo has been updated.'));
					$_GET['album_id'] = $_POST['gallery_album_id'];
					unset($_POST);
					return $this->on_photos_lists();
				}else{
					$this->_html .= $this->displayError($this->l('An error occurred during update, please try again.'));
				}

			$val = $_POST;
		}

		$this->_html .= '
		<fieldset>
			<form method="post" action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'" enctype="multipart/form-data">
				' . $this->fieldFormPhoto($val) . '
				<div class="margin-form">
					<input type="hidden" name="act" id="act" value="photos_edit" />
					<input type="hidden" name="id" id="id" value="' . $val['id'] . '" />
					<input type="submit" class="button" name="submitEditPhotos" value="'.$this->l('Update Photo').'" />
				</div>
			</form>
		</fieldset>';
	}
	private function on_photos_add(){
		global $cookie;

		if( Tools::isSubmit('submitAddAPhotos') ){
			if ( empty($_POST['gallery_album_id']) || empty($_FILES['name']['name']) )
				$this->_html .= $this->displayError($this->l('You must fill in mandatory fields'));
			else
				if ($this->addPhotos()){
					$this->_html .= $this->displayConfirmation($this->l('The Photo has been added.'));
					$_GET['album_id'] = $_POST['gallery_album_id'];
					unset($_POST);
					return $this->on_photos_lists();
				}else{
					$this->_html .= $this->displayError($this->l('An error occurred during save, please try again.'));
				}
			$val = $_POST;
		}

		$this->_html .= '
		<fieldset>
			<form method="post" action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'" enctype="multipart/form-data">
				' . $this->fieldFormPhoto($val) . '
				<div class="margin-form">
					<input type="hidden" name="act" id="act" value="photos_add" />
					<input type="submit" class="button" name="submitAddAPhotos" value="'.$this->l('Add Photo').'" />
				</div>
			</form>
		</fieldset>';
	}
	private function on_photos_lists(){

		global $currentIndex, $cookie, $adminObj;
		$languages = Language::getLanguages();

		$where = (isset($_GET['album_id'])) ? 'WHERE gallery_album_id = "' . $_GET['album_id'] . '" ' : '';
		$photos = $this->getPhotos($where);
		$cl = $currentIndex . '&configure=' .$this->name . '&token=' . Tools::getAdminTokenLite('AdminModules');

		$this->_html .= '
		<h3 class="blue space">'.$this->l('Photo list').'</h3>
		<table class="table">
			<tr>
				<th>'.$this->l('ID').'</th>
				<th>'.$this->l('Album Name').'</th>
				<th>'.$this->l('Thumbnail').'</th>
				<th>'.$this->l('Photo').'</th>
				<th>'.$this->l('Description').'</th>
				<th>'.$this->l('Actions').'</th>
			</tr>';

		if (!$photos)
			$this->_html .= '
			<tr>
				<td colspan="3">'.$this->l('There are no Photos.').'</td>
			</tr>';
		else
			foreach ($photos AS $photo){
				$promoted = ($album['promoted'] == 1 ) ? 'enabled.gif' : 'disabled.gif';
				$this->_html .= '
				<tr valign="top">
					<td>'.$photo['id'].'</td>
					<td>'.$photo['album'].'</td>
					<td>
						<img src="'.$this->_path.'img/'.$photo['thumb'].'" alt="" title="" />
					</td>
					<td>
						<img src="'.$this->_path.'img/'.$photo['name'].'" alt="" title="" style="width:50%;" />
					</td>
					<td>'.nl2br($photo['description']).'</td>
					<td>
						<a href=' . $cl  . "&act=photos_edit&id=" . $photo['id'] . '>
						<img src="../img/admin/edit.gif" alt="" title="" />
						</a>
						<a href=' . $cl  . "&act=photos_delete&id=" . $photo['id'] . '>
						<img src="../img/admin/delete.gif" alt="" title="" />
						</a>
					</td>
				</tr>';
			}
		$this->_html .= '</table>';
	}
	private function on_photos_delete(){
		if (isset($_GET['id']))
		{
			if (!is_numeric($_GET['id']) OR !$this->deletePhoto())
			 	$this->_html .= $this->displayError($this->l('An error occurred during deletion.'));
			else
			 	$this->_html .= $this->displayConfirmation($this->l('The Photo has been deleted.'));
		}
		$this->on_photos_lists();
	}
	private function on_configure(){
		global $cookie;
		$val = $this->getConfig();
		if( Tools::isSubmit('submitSaveConfig') ){
			if (
				empty($_POST['PS_GALLERY_THUMB_HEIGHT']) || empty($_POST['PS_GALLERY_THUMB_WIDTH'])
				||
				empty($_POST['PS_GALLERY_HEIGHT']) || empty($_POST['PS_GALLERY_WIDTH'])
			)
				$this->_html .= $this->displayError($this->l('You must fill in mandatory fields'));
			else
				if (
					$this->saveConfig('PS_GALLERY_THUMB_HEIGHT', $_POST['PS_GALLERY_THUMB_HEIGHT']) &&
					$this->saveConfig('PS_GALLERY_THUMB_WIDTH', $_POST['PS_GALLERY_THUMB_WIDTH']) &&
					$this->saveConfig('PS_GALLERY_HEIGHT', $_POST['PS_GALLERY_HEIGHT']) &&
					$this->saveConfig('PS_GALLERY_WIDTH', $_POST['PS_GALLERY_WIDTH'])
				){
					$this->_html .= $this->displayConfirmation($this->l('The Configuration has been updated.'));
					unset($_POST);
				}else{
					$this->_html .= $this->displayError($this->l('An error occurred during update, please try again.'));
				}

			$val = $this->getConfig();
		}


		$this->_html .= '
		<fieldset>
			<form method="post" action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'">
				<label>Thumbnail width</label>
				<div class="margin-form">
					<input type="text" name="PS_GALLERY_THUMB_WIDTH" id="PS_GALLERY_THUMB_WIDTH" value="'.(( isset($val['PS_GALLERY_THUMB_WIDTH'])) ? $val['PS_GALLERY_THUMB_WIDTH'] : '').'" />
					<sup> *</sup>
				</div>
				<label>Thumbnail height</label>
				<div class="margin-form">
					<input type="text" name="PS_GALLERY_THUMB_HEIGHT" id="PS_GALLERY_THUMB_HEIGHT" value="'.(( isset($val['PS_GALLERY_THUMB_HEIGHT'])) ? $val['PS_GALLERY_THUMB_HEIGHT'] : '').'" />
					<sup> *</sup>
				</div>

				<label>Image width</label>
				<div class="margin-form">
					<input type="text" name="PS_GALLERY_WIDTH" id="PS_GALLERY_WIDTH" value="'.(( isset($val['PS_GALLERY_WIDTH'])) ? $val['PS_GALLERY_WIDTH'] : '').'" />
					<sup> *</sup>
				</div>
				<label>Image height</label>
				<div class="margin-form">
					<input type="text" name="PS_GALLERY_HEIGHT" id="PS_GALLERY_HEIGHT" value="'.(( isset($val['PS_GALLERY_HEIGHT'])) ? $val['PS_GALLERY_HEIGHT'] : '').'" />
					<sup> *</sup>
				</div>

				<div class="margin-form">
					<input type="hidden" name="act" id="act" value="configure" />
					<input type="submit" class="button" name="submitSaveConfig" value="'.$this->l('Save Configuration').'" />
				</div>
			</form>
		</fieldset>';
	}
	/**
	 * }}}
	 **/

	private function fieldFormPhoto($val){
		$albums = $this->getAlbums();
		$sa = '<option> -- Album Name -- </option>';
		foreach( $albums AS $album):
			$selected = ($val['gallery_album_id'] == $album['id']) ? 'selected="selected"' : '';
			$sa .= '<option value="' . $album['id'] . '" ' . $selected . ' >' .$album['name']. '</option>';
		endforeach;
		$dir = dirname(__FILE__) . '/' . 'img' . '/';
		$img = '';
		if( empty($val['name']) == false && file_exists($dir . $val['name']) ){
			$img = '<br/><img src="'.$this->_path.'img/' . $val['name'] . '" alt="" title="" style="width:50%;" />';
		}
		return '
			<label>Album Name</label>
			<div class="margin-form">
				<select name="gallery_album_id" id="gallery_album_id" >'.$sa.'</select>
				<sup> *</sup>
			</div>
			<label>Photo</label>
			<div class="margin-form">
				<input type="file" name="name" id="name" value="'.(( isset($val['name'])) ? $val['name'] : '').'" />
				<sup> *</sup>
				' . $img . '
			</div>
			<label>Description</label>
			<div class="margin-form">
				<textarea name="description" id="description" name="" cols="40" rows="5">'.((isset($val['description'])) ? trim($val['description']) : '').'</textarea>
			</div>
		';
	}
	private function fieldFormAlbum($val){
		return '
			<label>Album Name</label>
			<div class="margin-form">
				<input type="text" name="name" id="name" value="'.(( isset($val['name'])) ? $val['name'] : '').'" />
				<sup> *</sup>
			</div>
			<label>Promoted</label>
			<div class="margin-form">
				<input type="checkbox" name="promoted" id="promoted" value="1"  ' . ((isset($val['promoted'])) ? 'checked="checked"' : '') . ' />
			</div>
			<label>Description</label>
			<div class="margin-form">
				<textarea name="description" id="description" name="" cols="40" rows="5">'.((isset($val['description'])) ? trim($val['description']) : '').'</textarea>
			</div>
		';
	}
	/**
	 * {{{ Models
	**/
	private function saveConfig($k,$v){
		$sql = '
			UPDATE '._DB_PREFIX_.'configuration
			SET
				`name`     = \''.pSQL($k).'\',
				`value`    = \''.pSQL($v).'\',
				`date_upd` = NOW()
			WHERE
				`name` = \''.pSQL($k).'\'
		';
		return (Db::getInstance()->Execute($sql));
	}
	private function getConfig() {
		$datas = array();
		/* Get  albums */
		$sql = 'SELECT a.* FROM '._DB_PREFIX_.'configuration AS a
			WHERE `name` LIKE "PS_GALLERY_%"
			ORDER BY a.id_configuration DESC';

		if (!$datas = Db::getInstance()->ExecuteS($sql)){
			return false;
		}
		$ret = array();
		foreach($datas AS $data){
			$ret[$data['name']] = $data['value'];
		}
		return $ret;
	}
	private function getAlbums() {
		$datas = array();
		/* Get  albums */
		if (!$datas = Db::getInstance()->ExecuteS('
			SELECT a.* FROM '._DB_PREFIX_.'gallery_albums AS a
			ORDER BY a.id DESC
		')){
			return false;
		}
		return $datas;
	}
	private function getAlbum($id) {
		$data = array();
		if (!$data = Db::getInstance()->getRow('
			SELECT a.* FROM '._DB_PREFIX_.'gallery_albums AS a
			WHERE id ="' . $id . '"
		')){
			return false;
		}
		return $data;
	}
	private function addAlbums() {
		if (!Db::getInstance()->Execute(
				'INSERT INTO '._DB_PREFIX_.'gallery_albums
				VALUES (
					NULL,
					\''.pSQL($_POST['name']).'\',
					\''.pSQL($_POST['promoted']).'\',
					\''.pSQL($_POST['description']).'\'
				)'
			) OR !$lastId = mysql_insert_id())
			return false;
		return true;
	}

	private function deleteAlbum(){
		return (Db::getInstance()->Execute('DELETE FROM '._DB_PREFIX_.'gallery_albums WHERE `id`='.(int)($_GET['id'])));
	}
	private function editAlbums(){
		return (Db::getInstance()->Execute(
			'UPDATE '._DB_PREFIX_.'gallery_albums
				SET `name`=\''.pSQL($_POST['name']).'\',
				`promoted`=\''.pSQL($_POST['promoted']).'\',
				`description`=\''.pSQL($_POST['description']).'\'
				WHERE `id`='. (int)($_POST['id']))
		);
	}
	private function getPhoto($id) {
		$data = array();
		if (!$data = Db::getInstance()->getRow('
			SELECT a.* FROM '._DB_PREFIX_.'gallery_photos AS a
			WHERE id ="' . $id . '"
		')){
			return false;
		}
		return $data;
	}
	private function editPhoto(){

		$oldData = $this->getPhoto($_POST['id']);
		if( isset($_FILES['name']["name"]) && empty($_FILES['name']["name"]) == false ){
			//update photo
			$config       = $this->getConfig();
			$dir          = dirname(__FILE__) . '/' . 'img' . '/';
			$ext          = end(explode('.', $_FILES['name']['name']));
			$imgName      = md5(rand() . date('Ymdhis') ) . "." . $ext;
			$imgThumb     = md5(rand() . date('Ymdhis') ) . "." . $ext;

			if ( move_uploaded_file($_FILES['name']["tmp_name"], $dir . $imgName ) === false ){
				return false;
			}

			$imgTool = new ImageTool();
			if( $imgTool->resize(array(
					'input'  =>  $dir . $imgName,
					'output' => $dir . $imgThumb,
					'width'  => $config['PS_GALLERY_THUMB_WIDTH'],
					'height' => $config['PS_GALLERY_THUMB_HEIGHT']
				))
			){
				unlink( $dir . $oldData['name'] );
				unlink( $dir . $oldData['thumb'] );
			}

		}else{
			//get from db
			$imgName   = $oldData['name'];
			$imgThumb  = $oldData['thumb'];
		}

		return (Db::getInstance()->Execute(
			'UPDATE '._DB_PREFIX_.'gallery_photos
			SET
				`gallery_album_id` = \''.pSQL($_POST['gallery_album_id']).'\',
				`name`  = \''.pSQL($imgName).'\',
				`thumb` = \''.pSQL($imgThumb).'\',
				`description` = \''.pSQL($_POST['description']).'\'
			WHERE `id`='. (int)($_POST['id']))
		);

	}
	private function addPhotos(){
		//handle File Upload
		$config       = $this->getConfig();
		$dir          = dirname(__FILE__) . '/' . 'img' . '/';
		$ext          = end(explode('.', $_FILES['name']['name']));
		$imgName      = md5(rand() . date('Ymdhis') ) . "." . $ext;
		$imgThumb     = md5(rand() . date('Ymdhis') ) . "." . $ext;

		if ( move_uploaded_file($_FILES['name']["tmp_name"], $dir . $imgName ) === false ){
			return false;
		}

		$imgTool = new ImageTool();
		if( $imgTool->resize(array(
				'input'  =>  $dir . $imgName,
				'output' => $dir . $imgThumb,
				'width'  => $config['PS_GALLERY_THUMB_WIDTH'],
				'height' => $config['PS_GALLERY_THUMB_HEIGHT']
			))
		){
			unlink( $dir . $imgName );
		}

		if (!Db::getInstance()->Execute(
				'INSERT INTO '._DB_PREFIX_.'gallery_photos
				VALUES (
					NULL,
					\''.pSQL($_POST['gallery_album_id']).'\',
					\''.pSQL($imgName).'\',
					\''.pSQL($imgThumb).'\',
					\''.pSQL($_POST['description']).'\'
				)'
			) OR !$lastId = mysql_insert_id())
			return false;
		return true;
	}
	private function getPhotos($where) {
		$datas = array();
		$sql = 'SELECT a.*, b.name AS album
			FROM '._DB_PREFIX_.'gallery_photos AS a
			LEFT JOIN '._DB_PREFIX_.'gallery_albums AS b on b.id = a.gallery_album_id
			' . $where . '
			ORDER BY a.id DESC';

		if (!$datas = Db::getInstance()->ExecuteS($sql)){
			return false;
		}
		return $datas;
	}
	private function deletePhoto(){
		$sql = 'SELECT * FROM '._DB_PREFIX_.'gallery_photos WHERE `id` = '.$_GET['id'].' ';
		$val = Db::getInstance()->getRow($sql);
		if( Db::getInstance()->Execute('DELETE FROM '._DB_PREFIX_.'gallery_photos WHERE `id`='.(int)($_GET['id'])) ){
			$dir = dirname(__FILE__) . '/' . 'img' . '/';
			unlink( $dir . $val['name']);
			unlink( $dir . $val['thumb']);
			return true;
		}
		return false;
	}
	/**
	 * }}} Model
	 **/

	public function hookLeftColumn( $params )
	{
		global $smarty;

		$sql = 'SELECT a.*, b.name AS album
			FROM '._DB_PREFIX_.'gallery_photos AS a
			LEFT JOIN '._DB_PREFIX_.'gallery_albums AS b on b.id = a.gallery_album_id
			ORDER BY RAND()
			LIMIT 5';
		$datas = Db::getInstance()->ExecuteS($sql);
		$smarty->assign(array(
			'datas' => $datas,
			'imgPath' => $this->_path.'img/',
			'modulePath' => $this->_path
		));
		if (!$datas) return false;
		return $this->display( __FILE__, 'gallery.tpl' );
	}

	public function hookRightColumn( $params )
	{
		return $this->hookLeftColumn( $params );
	}
}