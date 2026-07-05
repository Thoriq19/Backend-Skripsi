<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotifikasiController extends Controller
{
    /**
     * Get all notifikasi for a user.
     *
     * GET /api/notifikasi
     */
    public function index(Request $request): JsonResponse
    {
        $query = Notifikasi::query();

        // Filter by user
        if ($request->has('id_user')) {
            $query->where('id_user', $request->id_user);
        }

        // Filter by tipe
        if ($request->has('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        // Filter by read status
        if ($request->has('dibaca')) {
            $query->where('dibaca', $request->boolean('dibaca'));
        }

        $perPage = $request->get('per_page', 15);
        $notifikasi = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi retrieved successfully',
            'data'    => $notifikasi,
            'errors'  => null,
        ]);
    }

    /**
     * Create a new notifikasi.
     *
     * POST /api/notifikasi
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_user'      => 'required|integer|exists:users,id',
            'judul'        => 'required|string|max:255',
            'pesan'        => 'required|string',
            'tipe'         => 'sometimes|in:info,peringatan,pembayaran,laporan',
            'id_terkait'   => 'nullable|integer',
            'tipe_terkait' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $notifikasi = Notifikasi::create(array_merge(
            $request->all(),
            ['dibaca' => false]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi created successfully',
            'data'    => $notifikasi,
            'errors'  => null,
        ], 201);
    }

    /**
     * Get a specific notifikasi.
     *
     * GET /api/notifikasi/{id}
     */
    public function show(int $id): JsonResponse
    {
        $notifikasi = Notifikasi::find($id);

        if (!$notifikasi) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi retrieved successfully',
            'data'    => $notifikasi,
            'errors'  => null,
        ]);
    }

    /**
     * Mark a notifikasi as read.
     *
     * PUT /api/notifikasi/{id}/baca
     */
    public function markAsRead(int $id): JsonResponse
    {
        $notifikasi = Notifikasi::find($id);

        if (!$notifikasi) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $notifikasi->update(['dibaca' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi marked as read',
            'data'    => $notifikasi->fresh(),
            'errors'  => null,
        ]);
    }

    /**
     * Mark all notifikasi as read for a user.
     *
     * PUT /api/notifikasi/baca-semua
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_user' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $updated = Notifikasi::where('id_user', $request->id_user)
            ->where('dibaca', false)
            ->update(['dibaca' => true]);

        return response()->json([
            'success' => true,
            'message' => "{$updated} notifikasi marked as read",
            'data'    => ['updated_count' => $updated],
            'errors'  => null,
        ]);
    }

    /**
     * Get count of unread notifikasi for a user.
     *
     * GET /api/notifikasi/belum-dibaca
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $idUser = $request->get('id_user');

        if (!$idUser) {
            return response()->json([
                'success' => false,
                'message' => 'id_user is required',
                'data'    => null,
                'errors'  => null,
            ], 422);
        }

        $count = Notifikasi::where('id_user', $idUser)
            ->where('dibaca', false)
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'Unread count retrieved',
            'data'    => ['belum_dibaca' => $count],
            'errors'  => null,
        ]);
    }

    /**
     * Delete a notifikasi.
     *
     * DELETE /api/notifikasi/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $notifikasi = Notifikasi::find($id);

        if (!$notifikasi) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $notifikasi->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi deleted successfully',
            'data'    => null,
            'errors'  => null,
        ]);
    }
}
