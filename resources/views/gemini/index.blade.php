<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مستشار التخصصات الجامعية</title>
    <!-- مكتبة Tailwind CSS للتصميم المباشر -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Tajawal', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen p-4 md:p-8">

    <div class="max-w-3xl mx-auto">
        <div class="text-center mb-10">
            <h1 class="text-3xl md:text-4xl font-bold text-indigo-700 mb-3">مستشار التخصصات الأكاديمية</h1>
            <p class="text-gray-600">أدخل اسمك وتخصصك وسيقترح عليك الذكاء الاصطناعي أهم المواد التي يجب التركيز عليها</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 md:p-8 mb-8">
            <form action="{{ route('gemini.analyze') }}" method="POST" class="space-y-6">
                @csrf
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">اسم الطالب</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        placeholder="مثل: أحمد">
                </div>

                <div>
                    <label for="major" class="block text-sm font-medium text-gray-700 mb-2">التخصص الجامعي</label>
                    <input type="text" name="major" id="major" value="{{ old('major') }}" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        placeholder="مثل: علوم حاسوب، محاسبة، الخ...">
                </div>

                <button type="submit" 
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-4 rounded-xl transition duration-200">
                    تحليل التخصص واقتراح المواد
                </button>
            </form>
        </div>

        <!-- عرض رسائل الخطأ إن وجدت -->
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-8">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <!-- عرض النتيجة -->
        @if(session('result'))
            <div class="bg-white rounded-2xl shadow-sm border border-indigo-100 p-6 md:p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 pb-4 border-b border-gray-100">
                    <span class="text-indigo-600">✨</span> النتيجة المقترحة لك:
                </h2>
                <div class="prose prose-indigo max-w-none prose-p:text-gray-700 prose-headings:text-indigo-800 rtl">
                    {!! str()->markdown(session('result')) !!}
                </div>
            </div>
        @endif
    </div>

</body>
</html>