{{-- resources/views/components/toast.blade.php --}}
<div x-data class="fixed top-6 right-6 space-y-3 z-[9999] w-80">
    {{-- Success Toast --}}
    @if (session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
            class="fixed top-4 right-4 flex items-start gap-3 rounded-md border px-4 py-3 shadow text-sm z-50"
            style="background-color:#ECFDF5; border-color:#A7F3D0; color:#065F46;">
            <i class="fa-solid fa-circle-check text-lg mt-0.5"></i>
            <div>
                <div class="font-semibold">Berhasil</div>
                <div>{{ session('success') }}</div>
            </div>
            <button class="ml-auto" @click="show=false" aria-label="Tutup">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    {{-- Error Toast --}}
    @if (session('error'))
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
            class="fixed top-4 right-4 flex items-start gap-3 rounded-md border px-4 py-3 shadow text-sm z-50"
            style="background-color:#FFEAE6; border-color:#FCA5A5; color:#B91C1C;">
            <i class="fa-solid fa-circle-xmark text-lg mt-0.5"></i>
            <div>
                <div class="font-semibold">Gagal</div>
                <div>{{ session('error') }}</div>
            </div>
            <button class="ml-auto" @click="show=false" aria-label="Tutup">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif
</div>
{{-- End of Toast --}}