<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * @throws \Throwable
     */
    public function destroy(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // Verify password if provided
        if ($request->has('password')) {
            if (!Hash::check($request->password, $user->password)) {
                return back()->with('error', 'The password is incorrect.');
            }
        }

        DB::beginTransaction();

        try {
            // Check if user is the owner of any workspaces
            $ownedWorkspaces = $user->ownedWorkspaces()->get();

            foreach ($ownedWorkspaces as $workspace) {
                // If workspace has other members, transfer ownership to another admin or member
                if ($workspace->members()->where('user_id', '!=', $user->id)->exists()) {
                    // First try to find an admin
                    $newOwner = $workspace->members()
                        ->where('user_id', '!=', $user->id)
                        ->wherePivot('role', 'admin')
                        ->first();

                    // If no admin found, get any member
                    if (!$newOwner) {
                        $newOwner = $workspace->members()
                            ->where('user_id', '!=', $user->id)
                            ->first();
                    }

                    if ($newOwner) {
                        $workspace->owner_id = $newOwner->id;
                        $workspace->save();
                    } else {
                        // This shouldn't happen as we already checked for other members
                        throw new \Exception('No eligible user found to transfer workspace ownership.');
                    }
                } else {
                    // If workspace has no other members, delete it
                    $workspace->delete();
                }
            }

            // Remove user from all workspaces they're a member of
            $user->workspaces()->detach();

            // Delete user's personal data
            if ($user->employee) {
                $user->employee->delete();
            }

            // Delete any files associated with the user
            Storage::deleteDirectory('users/' . $user->id);

            // Anonymize user data instead of deleting the account completely
            // This preserves references in tasks, comments, etc.
            $user->name = 'Deleted User';
            $user->email = 'deleted_' . $user->id . '@deleted.fokus.app';
            $user->password = Hash::make(str_random(32));
            $user->remember_token = null;
            $user->email_verified_at = null;
            $user->deleted_at = now(); // If using soft deletes
            $user->save();

            // If not using soft deletes and you want to completely remove the user
            // $user->delete();

            DB::commit();

            // Log the user out
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('landing')
                ->with('success', 'Your account has been deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to delete your account: ' . $e->getMessage());
        }
    }
}
