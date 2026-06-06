from bs4 import BeautifulSoup

with open(r"C:\Users\anton\.gemini\antigravity-ide\brain\27f1b403-eaec-461a-85b6-d73111f35a9d\.system_generated\steps\666\content.md", "r", encoding="utf-8") as f:
    content = f.read()

soup = BeautifulSoup(content, 'html.parser')

products = soup.select('.product-item')
if not products:
    products = soup.select('.item-inner')
if not products:
    products = soup.select('.product-box')
if not products:
    products = soup.select('.item')

print(f"Found {len(products)} products")

if products:
    p = products[0]
    print(p.prettify()[:1000])
