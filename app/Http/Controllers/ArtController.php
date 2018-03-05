<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\User;
use App\Classes\Horde_Browser;
use Illuminate\Http\Request;

class ArtController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Art  $art
     * @return \Illuminate\Http\Response
     */
    public function show($id, $type)
    {
        $image       = '';
        $mime        = '';
        $filename    = '';
        $etag        = '';
        
        $userid=$id;
        $image = Image::where([['image_id','=', $id], ['image_type', '=', $type]])->first();
        if (!empty($image)) {
            $extension = $this->extension($image->mime);
            $filename  = htmlentities('user' . $id . '.' . $extension, ENT_QUOTES, config('system.site_charset'));
//            $filename  = scrub_out($filename . '.' . $extension);
        
            // Send the headers and output the image
            $browser = new Horde_Browser();
            if (!empty($etag)) {
                header('ETag: ' . $etag);
                header('Cache-Control: private');
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', time()));
            }
            header("Access-Control-Allow-Origin: *");
            $browser->downloadHeaders($filename, $mime, true);
            echo $image->image;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Art  $art
     * @return \Illuminate\Http\Response
     */
    public function edit(Art $art)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Art  $art
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Art $art)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Art  $art
     * @return \Illuminate\Http\Response
     */
    public function destroy(Art $art)
    {
        //
    }
    
    public function extension($mime)
    {
        $data      = explode("/", $mime);
        $extension = $data['1'];
    
        if ($extension == 'jpeg') {
            $extension = 'jpg';
        }
    
        return $extension;
    } // extension
}
