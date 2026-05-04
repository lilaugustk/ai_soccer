const fs = require('fs');
const file = process.argv[2];
const content = fs.readFileSync(file, 'utf8');

const stack = [];
const tags = content.match(/<(?!\/)([a-zA-Z0-9]+)(?=[^>]*>)|<\/([a-zA-Z0-9]+)>/g);

if (!tags) {
    console.log("No tags found");
    process.exit(0);
}

tags.forEach(tag => {
    if (tag.startsWith('</')) {
        const name = tag.substring(2, tag.length - 1);
        if (stack.length === 0) {
            console.log(`Unmatched closing tag: ${tag}`);
        } else {
            const last = stack.pop();
            if (last !== name) {
                console.log(`Mismatched tag: <${last}> closed by ${tag}`);
            }
        }
    } else {
        const name = tag.substring(1).split(/[ >]/)[0];
        // Ignore self-closing tags if needed, but here we just check structure
        stack.push(name);
    }
});

console.log(`Remaining open tags: ${stack.join(', ')}`);
