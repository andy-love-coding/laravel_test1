<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

class SessionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest', [
            'only' => 'create'
        ]);
    }

    public function create()
    {
        return view('sessions.create');
    }

    public function store(Request $request)
    {
        $credentials = $this->validate($request ,[
            'email' => 'required|email|max:255',
            'password' => 'required'
        ]);

        if(Auth::attempt($credentials, $request->has('remember'))){
            // 检查是否激活
            if(Auth::user()->activated) {
                // 已激活
                session()->flash('success', '欢迎回来');
                $fallback = route('users.show', Auth::user());
                return redirect()->intended($fallback); // intended()实现友好转向
            } else {
                // 未激活
                Auth::logout();
                session()->flash('warning', '你的账号还未激活，请检查邮箱中的注册邮件进行激活。');
                return redirect('/');
            }
        } else {
            session()->flash('danger', '很抱歉，邮箱与密码不匹配');
            return redirect()->back()->withInput();
        }
    }

    public function destroy()
    {
        Auth::logout();
        session()->flash('success', '成功退出');
        // return redirect()->route('login');
        return redirect('login');
    }
}
