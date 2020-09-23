<?php


class Xunfei
{
    protected $key = '9d1b2d1063df2eac';

    public function treeList () {
        $return = file_get_contents('http://api.kuyinyun.com/p/q_cols?a=' . $this->key . '&id=318797');
        return json_decode($return);
    }

    public function subList ($id, $px = 0) {
        $data['a'] = $this->key;
        $data['ps'] = 100;
        $data['px'] = $px;
        $data['id'] = $id;
        $url = 'http://api.kuyinyun.com/p/q_colres_vr?' . http_build_query($data);
        $return = file_get_contents($url);
        return json_decode($return);
    }
}