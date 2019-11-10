<?php


namespace app\app\controller;


use app\model\Banner;
use app\model\Book;
use app\model\Tags as Tag;
use think\Db;
use think\Request;

class Tags extends Base
{
    public function getList()
    {
        $tags = cache('tags');
        if (!$tags) {
            $tags = Tag::all();
            cache('tags', $tags, null, 'redis');
        }

        $result = [
            'success' => 1,
            'tags' => $tags
        ];
        return json($result);
    }

    public function getAreaList()
    {
        $areas = cache('areas');
        if (!$areas) {
            $areas = \app\model\Area::all();
            cache('areas', $areas, null, 'redis');
        }
        return json(['success' => 1, 'areas' => $areas]);
    }

    public function getBookList(Request $request)
    {
        $startItem = input('startItem');
        $pageSize = input('pageSize');


        $cate_selector = '全部';
        $area_selector = '全部';
        $end_selector = '全部';

        $map = array();
        $area = $request->param('area');
        if (is_null($area) || $area == '-1') {

        } else {
            $area_selector = $area;
            $map[] = ['area_id', '=', $area];
        }
        $tag = $request->param('tag');
        if (is_null($tag) || $tag == '全部') {

        } else {
            $cate_selector = $tag;
            $map[] = ['tags', 'like', '%' . $tag . '%'];
        }
        $end = $request->param('end');
        if (is_null($end) || $end == -1) {

        } else {
            $end_selector = $end;
            $map[] = ['end', '=', $end];
        }
        $books = Book::where($map)->order('id', 'desc')->limit($startItem,$pageSize)->select();
        return json([
            'success' => 1,
            'books' => $books,
            'cate_selector' => $cate_selector,
            'area_selector' => $area_selector,
            'end_selector' => $end_selector,
            'startItem' => $startItem,
            'pageSize' => $pageSize
        ]);
    }

    public function getBanners(){
        $num = input('num');
        $banners = cache('bannersHomepage');
        if (!$banners) {
            $banners = Banner::where('banner_order','>', 0)->order('banner_order','desc')->select();
            cache('bannersHomepage',$banners, null, 'redis');
        }
        foreach ($banners as &$banner) {
            $banner['pic_name'] = $this->imgUrl.'/static/upload/banner/'.$banner['pic_name'];
        }
        return json(['success' => 1, 'banners' => $banners]);
    }
}