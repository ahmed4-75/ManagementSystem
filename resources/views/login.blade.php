<h2>login</h2>

@if($errors->any())
    <p style="color:red">{{ $errors->first() }}</p>
@endif

<form action="{{ route('login') }}" method="POST">
    @csrf
    <input type="text" name="identification" placeholder="البريد أو الهاتف"><br><br>
    <input type="password" name="password" placeholder="كلمة المرور"><br><br>
    <label><input type="checkbox" name="remember" value="on"> remember me</label><br><br>
    <button type="submit">submit</button>
</form>
