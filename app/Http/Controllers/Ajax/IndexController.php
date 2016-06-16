<?php

namespace App\Http\Controllers\Ajax;

class IndexController extends AjaxController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('web');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function sidebar($category)
    {
        \Session::set('sidebar_tab', $category);
        return $this->xml_from_array(['sidebar' => view('includes.sidebar')]);
    }
}
