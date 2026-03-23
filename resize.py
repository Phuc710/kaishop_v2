import os
from PIL import Image

def generate_favicons(source_path, output_dir):
    if not os.path.exists(source_path):
        print(f"Error: Source file '{source_path}' not found.")
        return

    if not os.path.exists(output_dir):
        os.makedirs(output_dir)
        print(f"Created directory: {output_dir}")

    sizes = {
        "favicon-16x16.png": (16, 16),
        "favicon-32x32.png": (32, 32),
        "favicon-48x48.png": (48, 48),
        "apple-touch-icon.png": (180, 180),
        "android-chrome-192x192.png": (192, 192),
        "android-chrome-512x512.png": (512, 512),
    }

    try:
        with Image.open(source_path) as img:
            # Ensure image is RGBA for transparency
            if img.mode != 'RGBA':
                img = img.convert('RGBA')

            for name, size in sizes.items():
                resized = img.resize(size, Image.Resampling.LANCZOS)
                path = os.path.join(output_dir, name)
                resized.save(path)
                print(f"Generated: {name} ({size[0]}x{size[1]})")

            # Generate favicon.ico (standard multi-size ICO)
            ico_sizes = [(16, 16), (32, 32), (48, 48)]
            ico_path = os.path.join(output_dir, "favicon.ico")
            img.save(ico_path, format='ICO', sizes=ico_sizes)
            print(f"Generated: favicon.ico (contains 16, 32, 48)")

    except Exception as e:
        print(f"An error occurred: {e}")

if __name__ == "__main__":
    source = "favicon.png"
    output = "assets/favicon"
    generate_favicons(source, output)
