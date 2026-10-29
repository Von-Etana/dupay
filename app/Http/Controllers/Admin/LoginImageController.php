<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BasicSetting;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;

class LoginImageController extends Controller
{
    /**
     * The BasicSetting keys this page manages.
     * key => [label, resize (w,h) or null for original]
     */
    private array $slots = [
        'admin_login_logo'   => ['label' => 'Login Logo',            'resize' => [200, 200]],
        'admin_login_banner' => ['label' => 'Login Banner / Image',  'resize' => null],
        'admin_login_image2' => ['label' => 'Extra Image (optional)', 'resize' => null],
    ];

    public function edit()
    {
        // Ensure a row exists for each slot so getSiteSettings() never returns null unexpectedly.
        foreach ($this->slots as $key => $meta) {
            BasicSetting::firstOrCreate(
                ['column_name' => $key],
                [
                    'name'        => $meta['label'],
                    'description' => $meta['label'],
                    'model'       => 'LoginImage',
                    'input_type'  => '5',   // matches how existing image settings are typed
                    'rule'        => 'nullable',
                    'value'       => '',
                ]
            );
        }

        $data['page_title']  = 'Admin Login Images';
        $data['slots']       = $this->slots;
        $data['values']      = BasicSetting::whereIn('column_name', array_keys($this->slots))
                                    ->pluck('value', 'column_name');
        $data['breadcrumb']  = [
            ['title' => 'Home', 'url' => route('admin.index')],
            ['title' => 'Admin Login Images', 'url' => null],
        ];

        return view('admin.loginimage.edit', $data);
    }

    public function update(Request $request)
    {
        $request->validate([
            'admin_login_logo'   => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:4096',
            'admin_login_banner' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:4096',
            'admin_login_image2' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:4096',
        ]);

        foreach ($this->slots as $key => $meta) {
            // Remove image if the "clear" checkbox was ticked.
            if ($request->boolean('clear_' . $key)) {
                $this->saveValue($key, '');
                continue;
            }

            if ($request->hasFile($key)) {
                $image    = $request->file($key);
                $filename = $key . '-' . time() . '.' . $image->getClientOriginalExtension();
                $location = 'assets/images/' . $filename;

                $img = Image::make($image);
                if (!empty($meta['resize'])) {
                    [$w, $h] = $meta['resize'];
                    $img->resize($w, $h, function ($c) {
                        $c->aspectRatio();
                        $c->upsize();
                    });
                }
                $img->save(public_path($location));

                $this->saveValue($key, $location);
            }
        }

        return redirect()->back()->with([
            'message' => 'Login images updated successfully.',
            'type'    => 'success',
            'title'   => 'Success',
        ]);
    }

    private function saveValue(string $key, string $value): void
    {
        $row = BasicSetting::firstOrCreate(
            ['column_name' => $key],
            ['name' => $key, 'model' => 'LoginImage', 'input_type' => '5', 'rule' => 'nullable', 'value' => '']
        );
        $row->update(['value' => $value]);
    }
}
