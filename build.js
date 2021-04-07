const fs = require("fs");
const path = require("path");
const yaml = require("js-yaml");

console.log(
  JSON.stringify(
    process.argv.slice(2).reduce((acc, p) => {
      const entries = yaml.load(
        fs.readFileSync(path.resolve(process.cwd(), p), "utf8")
      );

      for (const entry of entries) {
        for (const endpoint of entry.endpoints) {
          delete endpoint.docs_url;
          delete endpoint.example_urls;
          delete endpoint.notes;
        }

        acc.push(entry);
      }

      return acc;
    }, []),
    null,
    4
  )
);
