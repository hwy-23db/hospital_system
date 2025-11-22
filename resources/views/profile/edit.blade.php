<h1>Edit Profile</h1>

<form action="{{ route('profile.update') }}" method="POST">
    @csrf
    <input type="text" name="name" value="{{ auth()->user()->name }}">
    <input type="email" name="email" value="{{ auth()->user()->email }}">
    <button type="submit">Update</button>
</form>
