<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app-layout.css') }}">
    @stack('styles')
</head>
<body>
    <div class="stars"></div>
    <div class="container">
        @yield('content_header')
        @yield('content')
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const starsContainer = document.querySelector('.stars');
            if (starsContainer) {
                const numStars = 100;

                for (let i = 0; i < numStars; i++) {
                    let star = document.createElement('div');
                    star.classList.add('star');
                    let size = Math.random() * 3;
                    star.style.width = size + 'px';
                    star.style.height = size + 'px';
                    star.style.top = Math.random() * 100 + '%';
                    star.style.left = Math.random() * 100 + '%';
                    star.style.animationDelay = Math.random() * 5 + 's';
                    star.style.animationDuration = Math.random() * 5 + 5 + 's';
                    starsContainer.appendChild(star);
                }
            }
        });
    </script>
    @stack('js')
</body>
</html>
