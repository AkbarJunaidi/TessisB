<?php

namespace App\Http\Controllers\Contact;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\ContactRequest;
use App\Models\Contact;
use App\Services\Contact\ContactService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __construct(
        protected ContactService $contactService
    ) {}

    /**
     * Menampilkan daftar Kontak (buku alamat client), dengan kartu
     * statistik, filter huruf awal nama (A-Z), & pencarian.
     */
    public function index(Request $request): View
    {
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
        return view('contact.create');
    }

    /**
     * Menyimpan Kontak baru.
     */
    public function store(ContactRequest $request): RedirectResponse
    {
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
        $matchedProjects = $contact->matchedProjects();

        return view('contact.show', compact('contact', 'matchedProjects'));
    }

    /**
     * Menampilkan form Edit Kontak.
     */
    public function edit(Contact $contact): View
    {
        return view('contact.edit', compact('contact'));
    }

    /**
     * Memperbarui data Kontak.
     */
    public function update(ContactRequest $request, Contact $contact): RedirectResponse
    {
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
        $this->contactService->deleteContact($contact);

        return redirect()
            ->route('contacts.index')
            ->with('success', 'Kontak berhasil dihapus.');
    }
}
