import os
import requests

# Updated dictionary with new links for the failed items only
failed_products = {
    # REGULAR
    "reg_linen_napkins.jpg": "https://images.unsplash.com/photo-1590650153855-d9e808231d41",
    "reg_cork_coasters.jpg": "https://images.unsplash.com/photo-1620662046624-9df716591060",
    "reg_marble_bookends.jpg": "https://images.unsplash.com/photo-1589310243389-96a5483213a8",
    "reg_incense_tray.jpg": "https://images.unsplash.com/photo-1602161405054-0f1e00e4708c",

    # HIGH-END
    "hi_alpaca_wool_throw.jpg": "https://images.unsplash.com/photo-1583847268964-b28dc8f51f92",
    "hi_dinnerware_set.jpg": "https://images.unsplash.com/photo-1580915411954-282cb1b0d780",
    "hi_indoor_olive_tree.jpg": "https://images.unsplash.com/photo-1617103023188-fb6c08d8b8b4",
    "hi_walnut_wall_shelf.jpg": "https://images.unsplash.com/photo-1594448053961-46487e914093",

    # RARE
    "rare_antique_gilded_mirror.jpg": "https://images.unsplash.com/photo-1618220179428-22790b461013",
    "rare_fossil_wood_table.jpg": "https://images.unsplash.com/photo-1533090161767-e6ffed986c88",
    "rare_bauhaus_lamp.jpg": "https://images.unsplash.com/photo-1507473885765-e6ed657f9971",
    "rare_medieval_tapestry.jpg": "https://images.unsplash.com/photo-1583160247711-2191776b4b91",
    "rare_victorian_gate.jpg": "https://images.unsplash.com/photo-1506191632512-e9460d979b88",
    "rare_petrified_coral.jpg": "https://images.unsplash.com/photo-1546190255-451a91afc548"
}

folder_name = "shop_assets"
os.makedirs(folder_name, exist_ok=True)

print(f"Repairing {len(failed_products)} missing images...")

for filename, url in failed_products.items():
    try:
        # Using Unsplash source redirect for better reliability
        response = requests.get(url, stream=True, timeout=15)
        if response.status_code == 200:
            file_path = os.path.join(folder_name, filename)
            with open(file_path, 'wb') as f:
                for chunk in response.iter_content(1024):
                    f.write(chunk)
            print(f"Fixed: {filename}")
        else:
            print(f"Still failing: {filename} (HTTP {response.status_code})")
    except Exception as e:
        print(f"Error on {filename}: {e}")

print("\nRepair complete. Check your /shop_assets folder!")
