<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Синхронизация данных</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sync-container {
            max-width: 800px;
            margin: 50px auto;
        }
        .sync-btn {
            min-width: 200px;
            margin: 10px;
            padding: 15px;
            font-size: 1.1rem;
        }
        .status-alert {
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container sync-container">
    <h1 class="text-center mb-4">Синхронизация с MPFit</h1>

    @if(session('success'))
        <div class="alert alert-success status-alert">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger status-alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="d-flex flex-column align-items-center">
        <form action="{{ route('sync.products') }}" method="POST" class="text-center">
            @csrf
            <button type="submit" class="btn btn-primary sync-btn">
                <i class="bi bi-box-seam"></i> Синхронизировать товары
            </button>
        </form>

        <form action="{{ route('sync.stocks') }}" method="POST" class="text-center">
            @csrf
            <button type="submit" class="btn btn-warning sync-btn">
                <i class="bi bi-boxes"></i> Синхронизировать остатки
            </button>
        </form>

        <form action="{{ route('sync.arrivals') }}" method="POST" class="text-center">
            @csrf
            <button type="submit" class="btn btn-info sync-btn">
                <i class="bi bi-truck"></i> Синхронизировать приемки
            </button>
        </form>
    </div>

    <div class="mt-5">
        <h4>Статус последней синхронизации:</h4>
        <ul class="list-group">
            <li class="list-group-item">Товары: <span class="badge bg-primary">Обновлено {{ now()->format('d.m.Y H:i') }}</span></li>
            <li class="list-group-item">Остатки: <span class="badge bg-warning text-dark">Обновлено {{ now()->format('d.m.Y H:i') }}</span></li>
            <li class="list-group-item">Приемки: <span class="badge bg-info text-dark">Обновлено {{ now()->format('d.m.Y H:i') }}</span></li>
        </ul>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
