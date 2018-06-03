<?php

namespace App\Http\Controllers;

use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class UsersController extends Controller {

	public function __construct() {
		$this->middleware('auth', [
			'except' => ['show', 'create', 'edit', 'store', 'index', 'confirmEmail'],
		]);
		$this->middleware('guest', [
			'only' => ['create'],
		]);
	}

	public function create() {
		return view('users.create');
	}

	public function store(Request $request) {
		$this->validate($request, [
			'name' => 'required|max:50',
			'email' => 'required|email|unique:users|max:255',
			'password' => 'required|confirmed|min:6',
		]);

		$user = User::create([
			'name' => $request->name,
			'email' => $request->email,
			'password' => bcrypt($request->password),
		]);
		$this->sendEmailConfirmationTo($user);

		Session()->flash('success', '注册邮件已发送，请到邮箱确认激活');
		return redirect('/');
	}

	public function show(User $user) {
		$statuses = $user->feed()->paginate(30);

		return view('users.show', compact('user', 'statuses'));
	}

	public function edit(User $user) {
		return view('users.edit', compact('user'));
	}

	public function update(User $user, Request $request) {
		$this->validate($request, [
			'name' => 'required|max:50',
			'password' => 'nullable|confirmed|min:6',
		]);

		$this->authorize('update', $user);

		$date = [];
		$date['name'] = $request->name;
		if ($request->password) {
			$date['password'] = bcrypt($request->password);
		}
		$user->update($date);

		session()->flash('success', '个人资料更新成功！');
		return redirect()->route('users.show', $user->id);
	}

	public function index() {
		$users = User::paginate(10);
		return view('users.index', compact('users'));
	}

	public function destroy(User $user) {
		$this->authorize('destroy', $user);

		$user->delete();

		session()->flash('success', '成功删除用户！');
		return back();
	}

	protected function sendEmailConfirmationTo($user) {
		$view = 'emails.confirm';
		$data = compact('user');
		$to = $user->email;
		$subject = "感谢注册 Blog 应用！请确认你的邮箱。";
		Mail::send($view, $data, function ($message) use ($to, $subject) {
			$message->to($to)->subject($subject);
		});
	}

	public function confirmEmail($token) {
		$user = User::where('activation_token', $token)->firstOrFail();

		$user->activated = true;
		$user->activation_token = null;
		$user->save();

		Auth::login($user);

		session()->flash('success', '恭喜你，激活成功！');
		return redirect()->route('users.show', compact('user'));
	}

	public function followings(User $user) {
		$users = $user->followings()->paginate(30);
		$title = '关注的人';

		return view('users.show_follow', compact('users', 'title'));
	}

	public function followers(User $user) {
		$users = $user->followers()->paginate(30);

		$title = '粉丝';

		return view('users.show_follow', compact('users', 'title'));
	}

}
