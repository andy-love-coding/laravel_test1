<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;
use Mail;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', [
            // 未登录用户访问权限
            'except' => ['show', 'create', 'store', 'index', 'confirmEmail']
        ]);

        $this->middleware('guest', [
            // 游客访问权限
            'only' => ['create']
        ]);
    }

    public function create()
    {
        return view('users.create');
    }
    
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:50',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|confirmed|min:6'
        ],[
            'name.required' => '名字都不写，想咋地？'
        ]);
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);
        
        // Auth::login($user); // 注册后自动登录
        // session()->flash('success', '欢迎，您将在这里开启一段新的旅程~');
        // return redirect()->route('users.show', $user->id);
        $this->sendEmailConfirmationTo($user);
        session()->flash('success', '验证邮件已发送到你的注册邮箱上，请注意查收。');
        return redirect('/');        
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);
        return view('users.edit', compact('user'));
    }

    public function update(User $user, Request $request)
    {
        $this->authorize('update', $user);
        $this->validate($request, [
            'name' => 'required|max:50',
            'password' => 'nullable|confirmed|min:6'
        ]);

        $data = [];
        $data['name'] = $request->name;
        if($request->password){
            $data['password'] = $request->password;
        }
        
        $user->update($data);

        session()->flash('success', '更新个人资料成功');
        return redirect()->route('users.show', [$user]);
    }

    public function index()
    {
        $users = User::paginate(5);
        return view('users.index', compact('users'));
    }

    public function destroy(User $user)
    {
        $user->delete();
        session()->flash('success', '删除用户成功!');
        return back();
    }

    protected function sendEmailConfirmationTo($user)
    {
        $view = 'emails.confirm';
        $data = compact('user');
        // $from = '123@qq.com';
        // $name = 'andy';
        $to = $user->email;
        $subject = '感谢注册 Weibo 应用！请确认你的邮箱。';

        // Mail::send($view, $data, function($message) use ($from, $name, $to, $subject) {
        //     $message->from($from, $name)->to($to)->subject($subject);
        // });
        Mail::send($view, $data, function($message) use ($to, $subject) {
            $message->to($to)->subject($subject);
        });
    }

    public function confirmEmail($token)
    {
        $user = User::where('activation_token', $token)->firstOrFail();

        $user->activated = true;
        $user->activation_token = null;
        $user->save();

        Auth::login($user);
        session()->flash('success', '恭喜你，激活成功！');
        return redirect()->route('users.show', $user);
    }
}
