<?php

namespace App\Http\Controllers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Models\Private_Msg;
use Illuminate\Http\Request;
use App\Models\User;
use App\Support\UI;

class PrivateMsgController extends Controller
{
    /**
     *  @var int $id
     */
    public $id;
    /**
     *  @var string $subject
     */
    public $subject;
    /**
     *  @var string $message
     */
    public $message;
    /**
     *  @var integer $from_user
     */
    public $from_user;
    /**
     *  @var integer $to_user
     */
    public $to_user;
    /**
     *  @var integer $creation_date
     */
    public $creation_date;
    /**
     *  @var boolean $is_read
     */
    public $is_read;
    
    /**
     *  @var string $f_subject
     */
    public $f_subject;
    /**
     *  @var string $f_message
     */
    public $f_message;
    /**
     *  @var string $link
     */
    public $link;
    /**
     *  @var string $f_link
     */
    public $f_link;
    /**
     *  @var string $f_from_user_link
     */
    public $f_from_user_link;
    /**
     *  @var string $f_to_user_link
     */
    public $f_to_user_link;
    /**
     *  @var string $f_creation_date
     */
    public $f_creation_date;
    
    protected $messages;
    protected $user;
    
    public function __construct(User $usermodel)
    {
        $this->user = $usermodel;
    }
    
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
        UI::flip_class(['odd','even']);
        $messages   = Private_Msg::select('*')->where('is_read', '=', 0)->paginate();
        $count = Private_Msg::select('*')->where('is_read', '=', 0)->count();
        $privateMsg = new Private_Msg();

        return view('privatemsg.index', compact('messages', 'privateMsg', 'count'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($username)
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
     * @param  \App\Models\PrivateMsg  $privateMsg
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $pvtMsg = Private_Msg::where('id', '=', $id)->get();
        return view('show');
    }

    public function reply($id)
    {
        //
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PrivateMsg  $privateMsg
     * @return \Illuminate\Http\Response
     */
    public function edit(PrivateMsg $privateMsg)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PrivateMsg  $privateMsg
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PrivateMsg $privateMsg)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PrivateMsg  $privateMsg
     * @return \Illuminate\Http\Response
     */
    public function destroy(PrivateMsg $privateMsg)
    {
        //
    }
}
