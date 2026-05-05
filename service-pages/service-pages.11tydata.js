import path from "node:path";
import { Liquid } from "liquidjs";
import markdownIt from "markdown-it";

const liquid = new Liquid({
  root: path.join(process.cwd(), "src/_includes"),
  extname: ".html",
});
const md = markdownIt({ html: true });

const renderDescription = async (data) => {
  if (!data.description) return "";
  const liquidRendered = await liquid.parseAndRender(data.description, data);
  return md.render(liquidRendered);
};

export default {
  eleventyComputed: {
    renderedDescription: renderDescription,
  },
};
