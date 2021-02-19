<?php
/*-----------------------------------------------------------------------------
 * Lightweight framework Rev 1.3
 *-----------------------------------------------------------------------------
 * 2006-07-24 : initial version
 * 2015-05-12 : rebuild by hide
 * 2014-09-10 : rebuild
 *
 * rebuild
 *
 * 書籍関連
 *----------------------------------------------------------------------------*/

class Publication extends UtilDao
{
    public static $_table = 't_publication';

    /**--------------------------------------------------------------------------------
     * 書籍
     */
    // 書籍情報
    public static function getPublication($id)
    {
        return parent::getData('t_publication', $id);
    }

    // 書籍名
    public static function getPublicationName($id)
    {
        return parent::getName('t_publication', $id);
    }

    // 書籍著者IDを取得
    public static function getPublicationAuthorId($publication_id, $author_id)
    {
        $res = ORM::for_table('t_publication_author')
            ->select('id')
            ->where(['publication_id' => $publication_id, 'author_id' => $author_id])
            ->find_one();
        return $res->id ?? '';
    }

    // 著者IDから書籍を取得
    public static function getPublicationsByAuthorId($author_id)
    {
        return ORM::for_table('t_publication_author')->table_alias('a')
            ->join('t_publication', ['a.publication_id', '=', 'b.id'], 'b')
            ->select_many('b.id', 'b.name')
            ->where('author_id', $author_id)
            ->find_array();
    }

    // ジャンルごとの書籍登録数
    // 公開日前でも表示する
    // release_status=1
    public static function getPublicationCntByGenreId($genre_id)
    {
        // 登録著書件数
        return ORM::for_table('t_publication')
            ->where(['del_flg' => '0', 'release_status' => '1'])
            ->where_raw(' (( `genre_id1` = ? ) OR ( `genre_id2` = ?))', [$genre_id, $genre_id])
            ->count();
    }
    // NEW BOOK 4件
    public static function getNewBooks(){
        return ORM::for_table('t_publication')
            ->where(['del_flg' => '0', 'release_status' => '1'])
            ->limit(4)
            ->order_by_desc('public_date')
            ->order_by_desc('id')
            ->find_array();
    }

    // ジャンル名
    public static function getGenreName($genre_id)
    {
        return parent::getName('m_genre', $genre_id);
    }

    // 関連書籍
    // 公開日前でも表示する
    // release_status=1
    public static function getRelated($id, $genre_id1, $genre_id2 = '')
    {
        $prm[] = $genre_id1;
        $prm[] = $genre_id1;
        $genre2tmp = '';
        if(!empty($genre_id2)){
            $genre2tmp = 'or genre_id1=? or genre_id2=?';
            $prm[] = $genre_id2;
            $prm[] = $genre_id2;
        }
        $orm = ORM::for_table('t_publication')
            ->where(['del_flg' => '0', 'release_status' => '1'])
            ->whereRaw("(genre_id1=? or genre_id2=? {$genre2tmp})", $prm)
            ->where_not_equal('id', $id);

        return $orm->order_by_desc('public_date')
            ->limit(4)
            ->find_array();
    }

    /**--------------------------------------------------------------------------------
     * 著者
     */
    public static function getAuthorName($id)
    {
        return parent::getName('t_author', $id);
    }

    public static function getAuthor($id)
    {
        return parent::getData('t_author', $id);
        //return ORM::for_table('t_author')->where('id', $id)->find_array();
    }

    // 著者リストを取得
    public static function getAuthors($publication_id)
    {
        return ORM::for_table('t_publication_author')->table_alias('a')
            ->join('t_author', ['a.author_id', '=', 'b.id'], 'b')
            ->where('a.del_flg', 0)
            ->where('a.publication_id', $publication_id)
            ->select_many('a.id', 'a.author_id', 'a.role', 'b.name', 'b.yomi', 'b.introduce')
            ->order_by_asc('sort')
            ->find_array();
    }

    // 著者ごとの書籍登録数
    public static function getPublicationCntByAuthorId($author_id)
    {
        return ORM::for_table('t_publication_author')->where(['author_id' => $author_id, 'del_flg' => 0])->count();
    }

    /**--------------------------------------------------------------------------------
     * 読者メッセージ
     */
    // 読者からのメッセージを取得
    public static function getMessage($publication_id, $release_status = null)
    {
        $orm = ORM::for_table('t_message');
        $orm->where(['del_flg' => '0', 'publication_id' => $publication_id]);
        if (!empty($release_status)) {
            $orm->where('release_status', $release_status);
        }
        return $orm->order_by_asc('sort')->find_array();
    }
    /**--------------------------------------------------------------------------------
     * 読者メッセージ
     */
    // 読者からのメッセージを取得
    public static function getInformation($publication_id, $release_status = null)
    {
        $today = date('Y-m-d');
        $orm = ORM::for_table('t_information');
        $orm->where(['del_flg' => '0', 'publication_id' => $publication_id]);
        if (!empty($release_status)) {
            $orm->where_raw("(release_status = ? AND date_from <= ? AND (date_to is null or date_to >=?))", [$release_status, $today, $today]);
        }
        return $orm->order_by_desc('date_from')->order_by_desc('id')->find_array();
    }

}
