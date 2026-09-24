<?php

namespace App\Http\Controllers\Contact;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\ContactRequest;
use App\Models\Contact;
use App\Services\Contact\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __construct(
        protected ContactService $contactService
    ) {}

    /**
     * [AJAX] Pencarian ringkas Kontak berdasarkan nama, dipakai fitur
     * autocomplete di field Client pada form Create/Edit Project - lihat
     * project/partials/form.blade.php. Terpisah dari index() karena
     * kebutuhannya beda: tidak perlu pagination/statistik, hasil
     * dibatasi & field-nya minimal saja (id, name, phone, email).
     */
    public function search(Request $request): JsonResponse
    {
        $keyword = (string) $request->query('q', '');

        $contacts = $this->contactService->search($keyword);

        return response()->json([
            'contacts' => $contacts->map(fn (Contact $c) => [
                'id'    => $c->id,
                'name'  => $c->name,
                'phone' => $c->phone,
                'email' => $c->email,
            ]),
        ]);
    }

    /**
     * Menampilkan daftar Kontak (buku alamat client), dengan kartu
     * statistik, filter huruf awal nama (A-Z), & pencarian.
     */
    public function index(Request $request): View
    {
        abort_unless(
            Auth::user()?->hasPermission('kontak', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat data kontak.'
        );

        $filters = $request->only('search', 'letter');

        $contacts = $this->contactService->getAllPaginated($filters);
        $stats    = $this->contactService->getStats();

        return view('contact.index', compact('contacts', 'filters', 'stats'));
    }

    /**
     * Menampilkan form Tambah Kontak.
     */
    public function create(): View
    {
        abort_unless(
            Auth::user()?->hasPermission('kontak', 'create'),
            403,
            'Anda tidak memiliki hak akses untuk menambah kontak.'
        );

        return view('contact.create');
    }

    /**
     * Menyimpan Kontak baru.
     */
    public function store(ContactRequest $request): RedirectResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('kontak', 'create'),
            403,
            'Anda tidak memiliki hak akses untuk menambah kontak.'
        );

        $this->contactService->createContact($request->validated());

        return redirect()
            ->route('contacts.index')
            ->with('success', 'Kontak berhasil ditambahkan.');
    }

    /**
     * Menampilkan Detail Kontak: info lengkap, catatan, & riwayat Project
     * yang cocok dengan nama Kontak ini (lihat Contact::matchedProjects()).
     */
    public function show(Contact $contact): View
    {
        abort_unless(
            Auth::user()?->hasPermission('kontak', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat data kontak.'
        );

        $matchedProjects = $contact->matchedProjects();

        return view('contact.show', compact('contact', 'matchedProjects'));
    }

    /**
     * Menampilkan form Edit Kontak.
     */
    public function edit(Contact $contact): View
    {
        abort_unless(
            Auth::user()?->hasPermission('kontak', 'edit'),
            403,
            'Anda tidak memiliki hak akses untuk mengubah data kontak.'
        );

        return view('contact.edit', compact('contact'));
    }

    /**
     * Memperbarui data Kontak.
     */
    public function update(ContactRequest $request, Contact $contact): RedirectResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('kontak', 'edit'),
            403,
            'Anda tidak memiliki hak akses untuk mengubah data kontak.'
        );

        $this->contactService->updateContact($contact, $request->validated());

        return redirect()
            ->route('contacts.index')
            ->with('success', 'Kontak berhasil diperbarui.');
    }

    /**
     * Menghapus Kontak.
     */
    public function destroy(Contact $contact): RedirectResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('kontak', 'delete'),
            403,
            'Anda tidak memiliki hak akses untuk menghapus kontak.'
        );

        $this->contactService->deleteContact($contact);

        return redirect()
            ->route('contacts.index')
            ->with('success', 'Kontak berhasil dihapus.');
    }
}
