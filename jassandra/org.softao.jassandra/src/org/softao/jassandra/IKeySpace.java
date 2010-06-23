/*
 * Copyright (C) 2010 Softao.Org
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
package org.softao.jassandra;

import java.util.Map;

/**
 * Represents a key space.
 * <p>
 * This is mapping to the <code>KeySpace</code> Cassandra defined.
 */
public interface IKeySpace {

	/**
	 * Gets the {@link IConnection connection} under which this key space is
	 * created.
	 * 
	 * @return the connection for this key space.
	 */
	IConnection getConnection();

	/**
	 * Gets the name of this key space.
	 * 
	 * @return the name of this key space
	 */
	String getName();

	/**
	 * Gets the column family with specified <code>name</code>.
	 * 
	 * @param name
	 *            the name of the column family.
	 * @return a column family with specified <code>name</code>, throws
	 *         {@link JassandraException} if column family not existed.
	 * @throws JassandraException
	 *             the jassandra exception
	 */
	IColumnFamily getColumnFamily(String name) throws JassandraException;

	/**
	 * Gets all column families in this key space.
	 * 
	 * @return name to column family map
	 * @throws JassandraException
	 */
	Map<String, IColumnFamily> getColumnFamilies() throws JassandraException;
}
