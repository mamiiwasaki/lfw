<?php
/*-----------------------------------------------------------------------------
 *	Lightweight framework Rev 1.3
 *-----------------------------------------------------------------------------
 *	2006-07-24 : initial version
 *	2015-05-12 : rebuild by hide
 *	2014-09-10 : rebuild
 *
 *	rebuild
 *----------------------------------------------------------------------------*/
require_once CLASSES_DIR . 'dao_util.trait.php';

class common_dao extends DBIO
{
    use daoUtilTrait;

    // DBで取得したデータを連想配列にして返す
    public function getArray($all)
    {
        if (empty($all)) {
            return [];
        }
        $arr = array();
        if (!empty($all)) {
            foreach ($all as $row) {
                $arr[$row[0]] = $row[1];
            }
        }
        return $arr;
    }
    // 0:すべて付きのカテゴリリストを返す
    function getCategoryList(){
        $cate_tmp = $this->getMasterAll('m_category');
        $category_list[0] = 'すべて';
        foreach($cate_tmp as $key=>$val){
            $category_list[$key] = $val;
        }
        return $category_list;
    }
}
