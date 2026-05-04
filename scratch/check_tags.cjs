const fs = require('fs');
const file = process.argv[2];
const content = fs.readFileSync(file, 'utf8');

const stack = [];
const lines = content.split('\n');

lines.forEach((line, lineNum) => {
    // Very basic tag finding
    const regex = /<([a-zA-Z0-9\-]+)(?=[^>]*>)|<\/([a-zA-Z0-9\-]+)>/g;
    let match;
    while ((match = regex.exec(line)) !== null) {
        const full = match[0];
        if (full.startsWith('</')) {
            const name = match[2];
            if (stack.length === 0) {
                console.log(`[L${lineNum + 1}] Unmatched closing tag: ${full}`);
            } else {
                const last = stack.pop();
                if (last.name !== name) {
                    console.log(`[L${lineNum + 1}] Mismatched tag: <${last.name}> (from L${last.line}) closed by ${full}`);
                }
            }
        } else {
            const name = match[1];
            // Skip void elements
            if (!['img', 'br', 'hr', 'input', 'meta', 'link'].includes(name.toLowerCase())) {
                stack.push({ name, line: lineNum + 1 });
            }
        }
    }
});

if (stack.length > 0) {
    console.log("Remaining open tags:");
    stack.forEach(s => console.log(`  <${s.name}> at L${s.line}`));
} else {
    console.log("All tags balanced!");
}
