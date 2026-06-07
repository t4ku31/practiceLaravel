<head>
    <title>ダッシュボード</title>
</head>



<body>
<div>

    @php
     $isEditable = false;
    @endphp     

    @auth
        <!-- @unless(Auth::check())
            <p>ログインしていません。</p>
        @else
            <p>ログインしています。</p>
        @endunless -->
        <form action="{{ route('tasks.store') }}" method="POST">
            @csrf
            <input id="title" name="title" type="text" placeholder="タスクを入力">
            <button type="submit">作成</button>
        </form>

       
        @if($tasks->isEmpty())
            <p>タスクはありません。</p>
        @else
        <h2>タスク一覧</h2>
        <ul>
            @foreach($tasks as $task)
                <form id="update-task-{{ $task->id }}" action="{{ route('tasks.update', $task) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('PUT')
                <input id="task-title-{{ $task->id }}" type="text" value="{{ $task->title }}" >
                    <!-- <button class="edit-button" data-task-id="{{ $task->id }}" onClick=@php $isEditable = !$isEditable; @endphp>編集</button>
                    <input type="text" value="{{ $task->title }}" @style(['display:none' => !$isEditable]) > -->
                    <!-- {{ $task->title }} -->
                    <form id="delete-task-{{ $task->id }}" action="{{ route('tasks.destroy', $task) }}" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit">削除</button>
                    </form>
                </input>
                </form>
            @endforeach
        </ul>
        @endif
      

        <form action="{{ route('authentication.userLogout') }}" method="POST">
            @csrf
            <button type="submit">ログアウト</button>
        </form>
    @endauth

    @guest
        <p>ゲストユーザーです。</p>

        <form action="{{ route('user.login') }}" method="get">
            @csrf
            <button type="submit">ログインへ</button>
        </form>
    @endguest
</div>
</body>